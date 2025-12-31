<?php
// Store Page with Kakao Maps (API)
// API Key provided by user.
// User Link: https://map.kakao.com/?q=%EB%9A%9C%EB%A0%88%EC%A5%AC%EB%A5%B4
// We will use the Keyword Search API.
?>
<div class="store-page">
    <div class="store-hero" style="background: #f4f4f4; padding: 60px 0; text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 20px;">매장찾기</h1>
        <p>가까운 뚜레쥬르 매장을 찾아보세요.</p>
    </div>

    <div class="store-content"
        style="max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; gap: 20px; height: 600px;">
        <!-- Left: List -->
        <div class="store-list-container" style="flex: 1; background: white; border: 1px solid #ddd; display: flex; flex-direction: column;">
            <div class="search-box" style="padding: 20px; border-bottom: 1px solid #eee; flex-shrink: 0;">
                <input type="text" id="keyword" value="뚜레쥬르" placeholder="검색어 입력"
                    style="width: 70%; padding: 10px; border: 1px solid #ddd;">
                <button onclick="searchPlaces()"
                    style="width: 25%; padding: 10px; background: #004d40; color: white; border: none;">검색</button>
            </div>
            <ul id="placesList" style="list-style: none; padding: 0; overflow-y: auto; flex: 1;"></ul>
            <div id="pagination" style="flex-shrink: 0;"></div>
        </div>

        <!-- Right: Map -->
        <div id="map" style="flex: 2; width: 100%; height: 100%; background: #eee;"></div>

        <script>
            // Script removed to avoid duplication with main.js
            // All map logic is handled in js/main.js initStorePage()
        </script>