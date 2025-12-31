<?php
require_once '../includes/config.php';
$conn = getDBConnection();

// Get all categories with hierarchy
$sql = "SELECT * FROM categories ORDER BY parent_id, sort_order";
$result = $conn->query($sql);
$categories = [];
$subCategories = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['parent_id'] === NULL || $row['parent_id'] == 0) {
            // Parent category
            $categories[] = $row;
        } else {
            // Sub category
            if (!isset($subCategories[$row['parent_id']])) {
                $subCategories[$row['parent_id']] = [];
            }
            $subCategories[$row['parent_id']][] = $row;
        }
    }
}

// Simple fetch all for now, filtering handled by JS or we could add PHP filtering
$sql = "SELECT * FROM products ORDER BY id DESC";
$result = $conn->query($sql);
$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
$conn->close();
?>
<!-- Product Page Content -->
<div class="product-page">
    <!-- Category Tabs -->
    <div class="product-category-tabs">
        <button class="category-btn new active" onclick="filterProducts('new', null)" data-category-id="new">NEW</button>
        <?php foreach ($categories as $cat): ?>
            <button class="category-btn" 
                    onclick="filterProducts('<?= strtolower($cat['name']) ?>', <?= $cat['id'] ?>)" 
                    data-category-id="<?= $cat['id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Sub Category -->
    <div class="product-sub-category" id="subCategoryContainer">
        <a href="#" class="active" onclick="filterSubCategory('all'); return false;">ALL</a>
    </div>

    <!-- Search -->
    <div class="product-search">
        <div class="product-search-wrapper">
            <input type="text" class="product-search-input" placeholder="Search" id="productSearch">
            <span class="material-symbols-outlined search-icon" style="color: #1f1f1f;">search</span>
        </div>
    </div>

    <!-- Product Grid -->
    <div class="product-page-grid" id="productGrid">
        <?php if (empty($products)): ?>
            <p style="text-align:center; width:100%;">등록된 제품이 없습니다.</p>
        <?php else: ?>
            <?php foreach ($products as $p): ?>
                <!-- Card triggers modal on click -->
                <div class="product-page-card" data-category="<?= strtolower(trim($p['category'])) ?>" data-subcategory="<?= strtolower(trim($p['subcategory'] ?? '')) ?>" data-new="<?= $p['is_new'] ?>"
                    data-name="<?= htmlspecialchars($p['name']) ?>"
                    data-id="<?= $p['id'] ?>"
                    data-price="<?= $p['price'] ?>"
                    data-image="<?= htmlspecialchars($p['image']) ?>"
                    data-description="<?= htmlspecialchars($p['description'] ?? '') ?>"
                    data-ingredients="<?= htmlspecialchars($p['ingredients'] ?? '') ?>"
                    data-nutrition="<?= htmlspecialchars(json_encode([
                        'calories' => $p['kcal'] ?? '',
                        'sugar' => $p['sugar'] ?? '',
                        'protein' => $p['protein'] ?? '',
                        'fat' => $p['fat'] ?? '',
                        'sodium' => $p['sodium'] ?? '',
                        'allergens' => $p['allergens'] ?? ''
                    ])) ?>"
                    onclick="openProductModalFromCard(this)">

                    <div class="product-page-image"
                        style="background-image: url('<?= htmlspecialchars($p['image'] ?: 'https://via.placeholder.com/150') ?>');">
                    </div>

                    <div class="product-page-info">
                        <p class="product-page-name"><?= htmlspecialchars($p['name']) ?></p>
                        <p class="product-page-price"><?= number_format($p['price']) ?>원</p>
                    </div>

                    <div class="product-page-action">
                        <button class="btn-card-add" onclick="event.stopPropagation(); addToCart(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>', <?= $p['price'] ?>, '<?= htmlspecialchars($p['image']) ?>')">장바구니</button>
                        <button class="btn-card-buy" onclick="event.stopPropagation(); buyNow(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>', <?= $p['price'] ?>, '<?= htmlspecialchars($p['image']) ?>')">구매하기</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .product-page-action {
        display: flex;
        gap: 8px;
        padding: 20px 15px 15px 15px;
        margin-top: 15px;
        border: none !important;
        border-top: none !important;
    }

    .btn-card-add, .btn-card-buy {
        flex: 1;
        padding: 14px;
        font-size: 15px;
        font-weight: 600;
        border-radius: 30px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-card-add {
        background-color: #fff;
        border: 1px solid #ddd;
        color: #333;
    }

    .btn-card-add:hover {
        border-color: #999;
    }

    .btn-card-buy {
        background-color: rgba(0, 92, 3, 0.1);
        border: none;
        color: #333;
    }

    .btn-card-buy:hover {
        background-color: rgba(0, 92, 3, 1);
        color: white;
    }
</style>

<script>
    // buyNow 함수 추가
    window.buyNow = function(id, name, price, image) {
        // 로그인 체크
        if (typeof IS_LOGGED_IN !== 'undefined' && !IS_LOGGED_IN) {
            if (confirm('로그인이 필요한 서비스입니다.\n로그인 페이지로 이동하시겠습니까?')) {
                loadPage('login');
            }
            return;
        }

        // 장바구니에 추가
        addToCart(id, name, price, image);
        
        // 바로 장바구니 페이지로 이동
        setTimeout(() => {
            loadPage('cart');
        }, 300);
    };

    // Store sub categories data from PHP
    const subCategoriesData = <?= json_encode($subCategories) ?>;
    let currentMainCategory = 'new';

    window.filterProducts = function (category, categoryId) {
        currentMainCategory = category;
        
        // Update active tab
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Find and activate the clicked button
        const clickedBtn = document.querySelector(`[data-category-id="${categoryId || category}"]`);
        if (clickedBtn) {
            clickedBtn.classList.add('active');
        }

        // Update sub categories
        const subCategoryContainer = document.getElementById('subCategoryContainer');
        subCategoryContainer.innerHTML = '<a href="#" class="active" onclick="filterSubCategory(\'all\'); return false;">ALL</a>';
        
        if (categoryId && subCategoriesData[categoryId]) {
            subCategoriesData[categoryId].forEach(sub => {
                const link = document.createElement('a');
                link.href = '#';
                link.textContent = sub.name;
                link.onclick = function(e) {
                    e.preventDefault();
                    filterSubCategory(sub.name.toLowerCase());
                    return false;
                };
                subCategoryContainer.appendChild(link);
            });
        }

        // Filter products
        const cards = document.querySelectorAll('.product-page-card');
        cards.forEach(card => {
            const cardCategory = card.dataset.category.toLowerCase().trim();
            const filterCategory = category.toLowerCase().trim();
            
            if (category === 'new') {
                if (card.dataset.new == '1') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            } else {
                if (cardCategory === filterCategory || category === 'all') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            }
        });
    };

    window.filterSubCategory = function(subCategory) {
        // Update active sub category
        document.querySelectorAll('.product-sub-category a').forEach(link => {
            link.classList.remove('active');
        });
        
        if (event && event.target) {
            event.target.classList.add('active');
        }

        const cards = document.querySelectorAll('.product-page-card');
        cards.forEach(card => {
            const cardCategory = card.dataset.category.toLowerCase().trim();
            const cardSubcategory = card.dataset.subcategory.toLowerCase().trim();
            const filterCategory = currentMainCategory.toLowerCase().trim();
            const filterSubcategory = subCategory.toLowerCase().trim();
            
            const matchesMain = (currentMainCategory === 'new') 
                ? card.dataset.new == '1' 
                : cardCategory === filterCategory;
            
            const matchesSub = (subCategory === 'all') 
                ? true 
                : cardSubcategory === filterSubcategory;
            
            card.style.display = (matchesMain && matchesSub) ? 'block' : 'none';
        });
    };

    // Search
    document.getElementById('productSearch').addEventListener('input', function (e) {
        const term = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.product-page-card');
        cards.forEach(card => {
            const name = card.dataset.name.toLowerCase();
            if (name.includes(term)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Initial filter is now handled by initProductPage in main.js
    // filterProducts('new');
</script>
