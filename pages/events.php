<?php
require_once '../includes/config.php';
$conn = getDBConnection();

// Fetch Events
$events = $conn->query("SELECT * FROM events WHERE type = 'event' ORDER BY created_at DESC");
// Fetch Notices
$notices = $conn->query("SELECT * FROM events WHERE type = 'notice' ORDER BY created_at DESC");
?>

<div class="event-page">
    <h1 class="event-page-title">EVENT</h1>
    <div class="event-tabs">
        <button class="event-tab active" onclick="switchEventTab('event')" id="eventTabBtn">이벤트</button>
        <button class="event-tab" onclick="switchEventTab('notice')" id="noticeTabBtn">공지사항</button>
    </div>

    <!-- Events Grid -->
    <div id="eventSection">
        <div class="event-page-grid">
            <?php
            if ($events->num_rows > 0) {
                while ($e = $events->fetch_assoc()):
                    $imgUrl = !empty($e['image']) ? $e['image'] : 'https://via.placeholder.com/400x300?text=Event';
                    ?>
                    <div class="event-page-card" data-title="<?= htmlspecialchars($e['title']) ?>"
                        data-date="<?= $e['start_date'] . ' ~ ' . $e['end_date'] ?>" data-image="<?= $imgUrl ?>"
                        data-content="<?= htmlspecialchars($e['content'] ?? '') ?>" onclick="openEventModal(this)">
                        <div class="event-card-bg" style="background-image: url('<?= $imgUrl ?>');"></div>
                        <div class="event-overlay">
                            <h3><?= htmlspecialchars($e['title']) ?></h3>
                            <?php if ($e['start_date'])
                                echo '<p>' . $e['start_date'] . ' ~ ' . $e['end_date'] . '</p>'; ?>
                        </div>
                    </div>
                <?php endwhile;
            } else {
                echo '<p class="placeholder-text">진행 중인 이벤트가 없습니다.</p>';
            }
            ?>
        </div>
        <!-- Pagination Placeholder -->
        <div class="event-pagination">
            <button class="event-pagination-arrow">&lt;</button>
            <button class="event-pagination-num active">1</button>
            <button class="event-pagination-arrow">&gt;</button>
        </div>
    </div>

    <!-- Notices List -->
    <div id="noticeSection" class="notice-section" style="display:none;">
        <div class="notice-list-container">
            <?php
            if ($notices->num_rows > 0) {
                while ($n = $notices->fetch_assoc()): ?>
                    <div class="notice-item" data-title="<?= htmlspecialchars($n['title']) ?>"
                        data-date="<?= date('Y-m-d', strtotime($n['created_at'])) ?>"
                        data-content="<?= htmlspecialchars($n['content'] ?? '') ?>" data-image="<?= $n['image'] ?? '' ?>"
                        onclick="openEventModal(this)">
                        <p class="notice-title"><?= htmlspecialchars($n['title']) ?></p>
                        <p class="notice-date"><?= date('Y-m-d', strtotime($n['created_at'])) ?></p>
                    </div>
                <?php endwhile;
            } else {
                echo '<p class="placeholder-text" style="text-align:center; padding:50px;">등록된 공지사항이 없습니다.</p>';
            }
            ?>
        </div>
    </div>
</div>

<?php $conn->close(); ?>

<!-- Event Modal -->
<!-- Event Modal -->
<div id="eventModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <button class="modal-close" onclick="closeEventModal()">&times;</button>
        <div class="modal-content-flex">
            <div class="modal-image-wrapper">
                <img id="eventModalImageTag" src="" alt="Event Image"
                    style="width: 100%; height: auto; object-fit: contain;">
            </div>
            <div class="modal-details" style="text-align: left;">
                <h2 id="eventModalTitle" style="font-size: 1.8rem; margin-bottom: 20px; color: #333;"></h2>
                <p id="eventModalDate" style="color: #666; margin-bottom: 30px; font-weight: 500;"></p>
                <div id="eventModalContent"
                    style="line-height: 1.8; color: #444; white-space: pre-wrap; font-size: 1rem;"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function switchEventTab(tab) {
        const eventSection = document.getElementById('eventSection');
        const noticeSection = document.getElementById('noticeSection');
        const eventTabBtn = document.getElementById('eventTabBtn');
        const noticeTabBtn = document.getElementById('noticeTabBtn');

        if (tab === 'event') {
            eventSection.style.display = 'block';
            noticeSection.style.display = 'none';

            eventTabBtn.classList.add('active');
            noticeTabBtn.classList.remove('active');
        } else {
            eventSection.style.display = 'none';
            noticeSection.style.display = 'block';

            eventTabBtn.classList.remove('active');
            noticeTabBtn.classList.add('active');
        }
    }

    function openEventModal(element) {
        const modal = document.getElementById('eventModal');
        const title = element.dataset.title;
        const date = element.dataset.date;
        const content = element.dataset.content;
        const image = element.dataset.image;

        document.getElementById('eventModalTitle').innerText = title;
        document.getElementById('eventModalDate').innerText = date;
        document.getElementById('eventModalContent').innerText = content;

        const imgTag = document.getElementById('eventModalImageTag');
        if (image && image !== 'null' && image !== '') {
            imgTag.src = image;
            imgTag.parentElement.style.display = 'flex';
        } else {
            imgTag.parentElement.style.display = 'none';
        }

        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
    }

    function closeEventModal() {
        const modal = document.getElementById('eventModal');
        modal.classList.remove('active');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    // Close on outside click
    window.onclick = function (event) {
        const modal = document.getElementById('eventModal');
        if (event.target == modal) {
            closeEventModal();
        }
    }
</script>