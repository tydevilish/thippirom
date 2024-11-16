<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

checkPageAccess(PAGE_NEWS);

// ดึงข้อมูลข่าวสารทั้งหมด
$categoryStmt = $conn->query("SELECT * FROM news_categories ORDER BY category_name");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงพารามิเตอร์การค้นหาและการเรียงลำดับ
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// สร้าง query พื้นฐาน
$query = "
    SELECT n.*, nc.category_name, u.fullname as author
    FROM news n
    LEFT JOIN news_categories nc ON n.category_id = nc.category_id
    LEFT JOIN users u ON n.created_by = u.user_id
    WHERE n.status = 'active'
";

// เพิ่มเงื่อนไขการค้นหา
$params = [];
if (!empty($search)) {
    $query .= " AND (n.title LIKE :search OR n.content LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($category)) {
    $query .= " AND n.category_id = :category";
    $params[':category'] = $category;
}

// เพิ่มการเรียงลำดับ
$query .= " ORDER BY n.created_at " . ($sort === 'oldest' ? 'ASC' : 'DESC');

$stmt = $conn->prepare($query);
$stmt->execute($params);
$news = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>| ระบบจัดการหมู่บ้าน</title>
    <link rel="icon" href="https://devcm.info/img/favicon.png">
    <link rel="stylesheet" href="../../src/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-modern">
    <div class="flex">
        <div id="sidebar" class="fixed top-0 left-0 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <!-- ปุ่ม toggle -->
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <?php renderMenu(); ?>
        </div>

        <div class="flex-1 ml-20">
            <nav class="bg-white shadow-sm px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">ข้อมูลข่าวสาร</h1>
                        <p class="text-sm text-gray-500 mt-1">ข่าวสารและประกาศจากทางหมู่บ้าน</p>
                    </div>
                </div>
            </nav>

            <!-- เพิ่มส่วน Search และ Filter -->
            <div class="p-6 pb-0">
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <form class="flex flex-wrap gap-4 items-end">
                        <!-- ช่องค้นหา -->
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหา</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                       placeholder="ค้นหาข่าวสาร...">
                            </div>
                        </div>

                        <!-- ตัวกรองประเภท -->
                        <div class="w-48">
                            <label class="block text-sm font-medium text-gray-700 mb-1">ประเภท</label>
                            <div class="relative">
                                <select name="category" 
                                        class="appearance-none w-full pl-4 pr-10 py-2 rounded-lg border border-gray-300 bg-white shadow-sm 
                                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="">ทั้งหมด</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>" 
                                                <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- ตัวเลือกการเรียงลำดับ -->
                        <div class="w-48">
                            <label class="block text-sm font-medium text-gray-700 mb-1">เรียงลำดับ</label>
                            <div class="relative">
                                <select name="sort" 
                                        class="appearance-none w-full pl-4 pr-10 py-2 rounded-lg border border-gray-300 bg-white shadow-sm 
                                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>ล่าสุด</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>เก่าที่สุด</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- ปุ่มค้นหา -->
                        <div>
                            <button type="submit" 
                                    class="inline-flex items-center px-6 py-2.5 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 
                                           text-white font-medium  
                                           hover:shadow-blue-500/50 hover:from-blue-700 hover:to-blue-800
                                           focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 
                                           transform transition-all duration-200 ease-in-out 
                                           hover:scale-[1.02] active:scale-[0.98]">
                                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                                ค้นหา
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- แสดงผลลัพธ์การค้นหา -->
            <div class="p-6 pt-0">
                <?php if (empty($news)): ?>
                    <div class="text-center py-8">
                        <p class="text-gray-500">ไม่พบข้มูลข่าวสาร</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($news as $item): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300 cursor-pointer"
                             onclick="showNewsDetails(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                            <?php if ($item['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                 class="w-full h-48 object-cover" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <?php endif; ?>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                        <?php echo htmlspecialchars($item['category_name']); ?>
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                    </span>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </h3>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal แสดงรายละเอียดข่าว -->
    <div id="newsDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-2xl mx-auto">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 id="newsTitle" class="text-2xl font-semibold text-white"></h3>
                        <button onclick="closeNewsModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="p-6">
                    <div id="newsImage" class="mb-6 hidden">
                        <img src="" alt="" class="w-full h-auto rounded-lg">
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <span id="newsCategory" class="text-sm font-medium text-blue-600 bg-blue-50 px-3 py-1 rounded"></span>
                        </div>
                        <span id="newsDate" class="text-sm text-gray-500"></span>
                    </div>
                    <div id="newsContent" class="text-gray-700 whitespace-pre-wrap"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // เพิ่ม JavaScript สำหรับ Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        const toggleIcon = toggleBtn.querySelector('svg path');
        const textElements = document.querySelectorAll('.opacity-0');
        let isExpanded = false;

        toggleBtn.addEventListener('click', () => {
            isExpanded = !isExpanded;
            if (isExpanded) {
                sidebar.classList.remove('w-20');
                sidebar.classList.add('w-64');
                toggleIcon.setAttribute('d', 'M15 19l-7-7 7-7');
                textElements.forEach(el => el.classList.remove('opacity-0'));
            } else {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                toggleIcon.setAttribute('d', 'M9 5l7 7-7 7');
                textElements.forEach(el => el.classList.add('opacity-0'));
            }
        });

        function showNewsDetails(news) {
            document.getElementById('newsTitle').textContent = news.title;
            document.getElementById('newsCategory').textContent = news.category_name;
            document.getElementById('newsDate').textContent = new Date(news.created_at).toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('newsContent').textContent = news.content;

            const newsImage = document.getElementById('newsImage');
            if (news.image_path) {
                newsImage.querySelector('img').src = news.image_path;
                newsImage.classList.remove('hidden');
            } else {
                newsImage.classList.add('hidden');
            }

            document.getElementById('newsDetailModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeNewsModal() {
            document.getElementById('newsDetailModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    </script>
</body>
</html>
