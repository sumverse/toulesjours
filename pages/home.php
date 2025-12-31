<?php
require_once '../includes/config.php';
$conn = getDBConnection();

// Fetch Sliders
$sliders = $conn->query("SELECT * FROM slider_images WHERE is_active = 1 ORDER BY order_num ASC");

// Fetch New Menu
$newProducts = $conn->query("SELECT * FROM products WHERE is_new = 1 ORDER BY id DESC LIMIT 4");

// Fetch Best Menu
$bestProducts = $conn->query("SELECT * FROM products WHERE is_best = 1 ORDER BY id DESC LIMIT 4");
?>
<!-- Hero Video -->
<div class="hero-section">
    <video id="heroVideo" autoplay muted playsinline class="hero-video">
        <source src="img/touslesjours_video.mp4?v=<?= time() ?>" type="video/mp4">
    </video>
    <div class="hero-content">
        <h1>매일 굽는 신선한 빵</h1>
        <p>뚜레쥬르에서 만나는 행복한 아침</p>
    </div>
</div>

<script>
// Ping-Pong Video Playback (정방향 → 역방향 반복)
(function() {
    const video = document.getElementById('heroVideo');
    let reverseInterval = null;
    
    video.addEventListener('ended', function() {
        // 정방향 재생 끝 → 역방향 시작
        video.currentTime = video.duration;
        
        reverseInterval = setInterval(function() {
            if (video.currentTime <= 0) {
                // 역방향 재생 끝 → 정방향 다시 시작
                clearInterval(reverseInterval);
                video.currentTime = 0;
                video.play();
            } else {
                video.currentTime -= 0.033; // 30fps 역재생
            }
        }, 33);
    });
    
    // 페이지 로드 시 자동 재생
    video.play().catch(e => console.log('Autoplay prevented:', e));
})();
</script>

    </div>
</div>

<!-- New Menu Section -->
<section class="home-section">
    <h2 class="home-section-title">New Menu</h2>
    <div class="product-slider-wrapper">
        <button class="slider-btn prev">❮</button>
        <div class="product-slider-container">
            <div class="product-slider-track">
                <?php
                $newProducts = $conn->query("SELECT * FROM products WHERE is_new = 1 ORDER BY id DESC LIMIT 12");
                while ($p = $newProducts->fetch_assoc()): ?>
                    <div class="home-product-card slide-item" onclick="goToProductCategory('new')">
                        <div class="home-product-image"
                            style="background-image: url('<?= $p['image'] ?: 'https://via.placeholder.com/150' ?>')"></div>
                        <p class="home-product-name"><?= htmlspecialchars($p['name']) ?></p>
                        <p class="home-product-price"><?= number_format($p['price']) ?>원</p>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <button class="slider-btn next">❯</button>
    </div>
</section>

<!-- Best Menu Section -->
<section class="home-section">
    <h2 class="home-section-title">Best Menu</h2>
    <div class="product-slider-wrapper">
        <button class="slider-btn prev">❮</button>
        <div class="product-slider-container">
            <div class="product-slider-track">
                <?php
                $bestProducts = $conn->query("SELECT * FROM products WHERE is_best = 1 ORDER BY id DESC LIMIT 12");
                while ($p = $bestProducts->fetch_assoc()): ?>
                    <div class="home-product-card slide-item"
                        onclick="goToProductCategory('<?= strtolower($p['category']) ?>')">
                        <div class="home-product-image"
                            style="background-image: url('<?= $p['image'] ?: 'https://via.placeholder.com/150' ?>')"></div>
                        <p class="home-product-name"><?= htmlspecialchars($p['name']) ?></p>
                        <p class="home-product-price"><?= number_format($p['price']) ?>원</p>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <button class="slider-btn next">❯</button>
    </div>
</section>

<!-- Gift Section (Static for now or fetch categories) -->
<section class="gift-section">
    <h2 class="home-section-title">Gift</h2>
    <div class="product-slider-wrapper">
        <button class="slider-btn prev">❮</button>
        <div class="product-slider-container">
            <div class="product-slider-track">
                <?php
                // Just fetch some 'gift' category or random
                $gifts = $conn->query("SELECT * FROM products WHERE category = 'gift' LIMIT 12");
                while ($g = $gifts->fetch_assoc()):
                    ?>
                    <div class="home-product-card slide-item" onclick="goToProductCategory('gift')">
                        <div class="home-product-image"
                            style="background-image: url('<?= $g['image'] ?: 'https://via.placeholder.com/150' ?>')"></div>
                        <p class="home-product-name"><?= htmlspecialchars($g['name']) ?></p>
                        <p class="home-product-price"><?= number_format($g['price']) ?>원</p>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <button class="slider-btn next">❯</button>
    </div>
</section>

<!-- Event Banner Section -->
<section class="home-event-section">
    <h2 class="home-section-title">Event</h2>
    <div class="home-event-grid">
        <?php
        $evts = $conn->query("SELECT * FROM events WHERE type='event' ORDER BY created_at DESC LIMIT 3");
        while ($e = $evts->fetch_assoc()):
            $imgUrl = !empty($e['image']) ? $e['image'] : 'https://via.placeholder.com/400x300?text=Event';
            ?>
            <div class="home-event-card" data-title="<?= htmlspecialchars($e['title']) ?>"
                data-date="<?= $e['start_date'] . ' ~ ' . $e['end_date'] ?>"
                data-content="<?= htmlspecialchars($e['content'] ?? '') ?>" data-image="<?= $imgUrl ?>"
                onclick="openHomeEventModal(this)">
                <div class="home-event-bg" style="background-image: url('<?= $imgUrl ?>');"></div>
                <div class="home-event-overlay">
                    <h3><?= htmlspecialchars($e['title']) ?></h3>
                    <p><?= $e['start_date'] ?> ~ <?= $e['end_date'] ?></p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</section>

<!-- Home Event Modal -->
<!-- Home Event Modal -->
<div id="homeEventModal" class="modal-overlay" style="display: none; z-index: 2000;">
    <div class="modal-container">
        <button class="modal-close" onclick="closeHomeEventModal()">&times;</button>
        <div class="modal-content-flex">
            <div class="modal-image-wrapper">
                <img id="homeEventModalImageTag" src="" alt="Event Image"
                    style="width: 100%; height: auto; object-fit: contain;">
            </div>
            <div class="modal-details" style="text-align: left;">
                <h2 id="homeEventModalTitle" style="font-size: 1.8rem; margin-bottom: 20px; color: #333;"></h2>
                <p id="homeEventModalDate" style="color: #666; margin-bottom: 30px; font-weight: 500;"></p>
                <div id="homeEventModalContent"
                    style="line-height: 1.8; color: #444; white-space: pre-wrap; font-size: 1rem;"></div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); ?>

<script>
    // Hero Slider
    (function () {
        const slides = document.querySelectorAll('.hero-slide');
        if (slides.length > 1) {
            let current = 0;
            setInterval(() => {
                slides[current].classList.remove('active');
                current = (current + 1) % slides.length;
                slides[current].classList.add('active');
            }, 3000);
        }
    })();

    // Product Carousel Slider - 무한 스크롤
    (function () {
        const sliders = document.querySelectorAll('.product-slider-wrapper');

        sliders.forEach(wrapper => {
            const track = wrapper.querySelector('.product-slider-track');
            const container = wrapper.querySelector('.product-slider-container');
            const prevBtn = wrapper.querySelector('.prev');
            const nextBtn = wrapper.querySelector('.next');
            const items = Array.from(track.querySelectorAll('.slide-item'));

            if (items.length === 0) return;

            const totalItems = items.length;
            const cloneCount = 8; // 앞뒤로 8개씩 복제

            // 뒤쪽 카드들을 앞에 복제
            for (let i = totalItems - cloneCount; i < totalItems; i++) {
                const clone = items[i].cloneNode(true);
                clone.classList.add('clone');
                track.insertBefore(clone, track.firstChild);
            }

            // 앞쪽 카드들을 뒤에 복제
            for (let i = 0; i < cloneCount; i++) {
                const clone = items[i].cloneNode(true);
                clone.classList.add('clone');
                track.appendChild(clone);
            }

            let currentIndex = cloneCount; // 실제 첫 카드 위치에서 시작
            let isTransitioning = false;

            // 카드 1개 너비 계산
            const getItemWidth = () => {
                const allItems = track.querySelectorAll('.slide-item');
                return allItems[0].offsetWidth + 20; // gap 포함
            };

            const updateSlider = (smooth = true) => {
                const itemWidth = getItemWidth();
                const offset = -currentIndex * itemWidth;
                
                if (smooth) {
                    track.style.transition = 'transform 0.5s ease';
                } else {
                    track.style.transition = 'none';
                }
                
                track.style.transform = `translateX(${offset}px)`;
            };

            const nextSlide = () => {
                if (isTransitioning) return;
                isTransitioning = true;
                
                currentIndex++;
                updateSlider(true);

                // 복제된 끝에 도달하면 실제 시작으로 점프
                if (currentIndex >= totalItems + cloneCount) {
                    setTimeout(() => {
                        currentIndex = cloneCount;
                        updateSlider(false); // 트랜지션 없이 점프
                        setTimeout(() => {
                            isTransitioning = false;
                        }, 50);
                    }, 500); // 트랜지션 끝난 후
                } else {
                    setTimeout(() => {
                        isTransitioning = false;
                    }, 500);
                }
            };

            const prevSlide = () => {
                if (isTransitioning) return;
                isTransitioning = true;
                
                currentIndex--;
                updateSlider(true);

                // 복제된 시작에 도달하면 실제 끝으로 점프
                if (currentIndex < cloneCount) {
                    setTimeout(() => {
                        currentIndex = totalItems + cloneCount - 1;
                        updateSlider(false); // 트랜지션 없이 점프
                        setTimeout(() => {
                            isTransitioning = false;
                        }, 50);
                    }, 500);
                } else {
                    setTimeout(() => {
                        isTransitioning = false;
                    }, 500);
                }
            };

            // 초기 위치 설정
            updateSlider(false);

            // 윈도우 리사이즈 시 재계산
            window.addEventListener('resize', () => updateSlider(false));

            // Auto Slide - 2초마다 1개씩
            let autoSlide = setInterval(nextSlide, 3000);

            // Button Events
            nextBtn.addEventListener('click', () => {
                clearInterval(autoSlide);
                nextSlide();
                autoSlide = setInterval(nextSlide, 3000);
            });

            prevBtn.addEventListener('click', () => {
                clearInterval(autoSlide);
                prevSlide();
                autoSlide = setInterval(nextSlide, 3000);
            });

            // Pause on hover
            wrapper.addEventListener('mouseenter', () => clearInterval(autoSlide));
            wrapper.addEventListener('mouseleave', () => autoSlide = setInterval(nextSlide, 3000));
        });
    })();
</script>
<script>
    function openHomeEventModal(element) {
        const modal = document.getElementById('homeEventModal');
        const title = element.dataset.title;
        const date = element.dataset.date;
        const content = element.dataset.content;
        const image = element.dataset.image;

        document.getElementById('homeEventModalTitle').innerText = title;
        document.getElementById('homeEventModalDate').innerText = date;
        document.getElementById('homeEventModalContent').innerText = content;

        const imgTag = document.getElementById('homeEventModalImageTag');
        if (image && image !== 'null' && image !== '') {
            imgTag.src = image;
            imgTag.parentElement.style.display = 'flex';
        } else {
            imgTag.parentElement.style.display = 'none';
        }

        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
    }

    function closeHomeEventModal() {
        const modal = document.getElementById('homeEventModal');
        modal.classList.remove('active');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    // Close on outside click
    window.addEventListener('click', function (e) {
        const modal = document.getElementById('homeEventModal');
        if (e.target == modal) {
            closeHomeEventModal();
        }
    });
</script>