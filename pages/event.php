<!-- Event Page Content -->
<div class="event-page">
    <h1 class="event-page-title">EVENT</h1>

    <!-- Event Tabs -->
    <div class="event-tabs">
        <button class="event-tab active" onclick="switchEventTab('event')" id="eventTabBtn">이벤트</button>
        <button class="event-tab" onclick="switchEventTab('notice')" id="noticeTabBtn">공지사항</button>
    </div>

    <!-- Event Section -->
    <div id="eventSection">
        <!-- Event Grid -->
        <div class="event-page-grid">
            <div class="event-page-card">
                <div class="event-page-image">이미지</div>
            </div>
            <div class="event-page-card">
                <div class="event-page-image">이미지</div>
            </div>
            <div class="event-page-card">
                <div class="event-page-image">이미지</div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="event-pagination">
            <button class="event-pagination-arrow">&lt;</button>
            <button class="event-pagination-num active">1</button>
            <button class="event-pagination-num">2</button>
            <button class="event-pagination-arrow">&gt;</button>
        </div>
    </div>

    <!-- Notice Section (Hidden by default) -->
    <div id="noticeSection" class="notice-section">
        <div class="notice-list-container">
            <div class="notice-item">
                <p class="notice-title">공지사항 제목 1</p>
                <p class="notice-date">2024-01-01</p>
            </div>
            <div class="notice-item">
                <p class="notice-title">공지사항 제목 2</p>
                <p class="notice-date">2024-01-02</p>
            </div>
            <div class="notice-item">
                <p class="notice-title">공지사항 제목 3</p>
                <p class="notice-date">2024-01-03</p>
            </div>
        </div>
    </div>
</div>