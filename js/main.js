/* ===========================================
   Main JavaScript
   =========================================== */

// Global State
let currentPage = "home";

// Cart Added Modal Functions
function showCartModal() {
  const modal = document.getElementById('cartAddedModal');
  if (modal) {
    modal.style.display = 'flex';
  }
}

function closeCartModal() {
  const modal = document.getElementById('cartAddedModal');
  if (modal) {
    modal.style.display = 'none';
  }
}

function goToCart() {
  closeCartModal();
  loadPage('cart');
}

// 장바구니 DB 동기화 함수
function saveCartToDB() {
  if (typeof IS_LOGGED_IN !== 'undefined' && IS_LOGGED_IN) {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    
    fetch('api/cart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'save',
        cart: JSON.stringify(cart)
      })
    }).catch(err => console.error('Cart save error:', err));
  }
}

function loadCartFromDB() {
  if (typeof IS_LOGGED_IN !== 'undefined' && IS_LOGGED_IN) {
    fetch('api/cart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'load'
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.cart) {
        localStorage.setItem('cart', JSON.stringify(data.cart));
        updateCartBadge();
      }
    })
    .catch(err => console.error('Cart load error:', err));
  }
}

// Load Page Content
function loadPage(page) {
  currentPage = page;
  localStorage.setItem("lastPage", page); // Persist page
  const content = document.getElementById("home_content");

  // Update active navigation
  updateNavigation(page);

  // Load page content via PHP include
  fetch(`pages/${page}.php`)
    .then((response) => {
      if (response.ok) {
        return response.text();
      }
      throw new Error("Page not found");
    })
    .then((html) => {
      content.innerHTML = html;
      window.scrollTo(0, 0);

      // Execute scripts in the loaded content
      executeScripts(content);

      // Initialize page-specific functionality
      initPageFunctions(page);
    })
    .catch((error) => {
      console.log("Loading default content for:", page);
      loadDefaultContent(page);
    });
}

// Execute scripts in dynamically loaded content
function executeScripts(container) {
  const scripts = container.querySelectorAll("script");
  scripts.forEach((script) => {
    if (script.src) {
      // External script - create and append
      const newScript = document.createElement("script");
      newScript.src = script.src;
      document.head.appendChild(newScript);
    } else {
      // Inline script - execute in global scope
      try {
        // Use indirect eval for global scope execution
        (0, eval)(script.textContent);
      } catch (e) {
        console.error("Error executing script:", e);
      }
    }
  });
}

// Update Navigation Active State
function updateNavigation(page) {
  const navLinks = document.querySelectorAll("nav ul li a");
  navLinks.forEach((link) => {
    link.classList.remove("active");
    const linkPage = link
      .getAttribute("onclick")
      ?.match(/loadPage\('(\w+)'\)/)?.[1];
    if (linkPage === page) {
      link.classList.add("active");
    }
  });
}

// Mobile Menu Toggle
function toggleMobileMenu() {
  const drawer = document.querySelector(".mobile-drawer");
  const overlay = document.querySelector(".mobile-overlay");
  drawer.classList.toggle("active");
  overlay.classList.toggle("active");
}

// Cart Functions
function addToCart(id, name, price, image) {
  // Check Login
  if (typeof IS_LOGGED_IN !== "undefined" && !IS_LOGGED_IN) {
    if (
      confirm(
        "로그인이 필요한 서비스입니다.\n로그인 페이지로 이동하시겠습니까?"
      )
    ) {
      loadPage("login");
    }
    return;
  }

  const cart = JSON.parse(localStorage.getItem("cart") || "[]");
  const existingItem = cart.find((item) => item.id == id);

  if (existingItem) {
    existingItem.quantity = parseInt(existingItem.quantity) + 1;
  } else {
    cart.push({ id, name, price, image, quantity: 1 });
  }

  localStorage.setItem("cart", JSON.stringify(cart));
  updateCartBadge();
  
  // DB에 저장
  saveCartToDB();
  
  // Show cart added modal
  showCartModal();
}

function updateCartBadge() {
  const cart = JSON.parse(localStorage.getItem("cart") || "[]");
  const count = cart.reduce((sum, item) => sum + parseInt(item.quantity), 0);
  const badges = document.querySelectorAll(".cart-count");
  badges.forEach((badge) => (badge.textContent = count));
}

// Update badge on load
document.addEventListener("DOMContentLoaded", updateCartBadge);

// Load Default Content (fallback)
function loadDefaultContent(page) {
  const content = document.getElementById("home_content");

  switch (page) {
    case "home":
      content.innerHTML = getHomeContent();
      break;
    case "products":
      content.innerHTML = getProductContent();
      break;
    case "stores":
      content.innerHTML = getStoreContent();
      break;
    case "events":
      content.innerHTML = getEventContent();
      break;
    default:
      content.innerHTML = '<div class="section"><h2>페이지 준비중</h2></div>';
  }

  window.scrollTo(0, 0);
}

// Initialize Page Functions
function initPageFunctions(page) {
  switch (page) {
    case "products":
      initProductPage();
      break;
    case "stores":
      initStorePage();
      break;
    case "events":
      initEventPage();
      break;
  }
}

// Product Page Functions
function initProductPage() {
  // Category button active state
  const categoryBtns = document.querySelectorAll(".category-btn");
  categoryBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      categoryBtns.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
    });
  });
}

function filterProducts(category) {
  console.log("Filtering products by:", category);
  const categoryBtns = document.querySelectorAll(".category-btn");
  categoryBtns.forEach((btn) => {
    btn.classList.remove("active");
    if (btn.textContent.toLowerCase() === category.toLowerCase()) {
      btn.classList.add("active");
    }
  });
}

function filterSubCategory(subcat) {
  console.log("Filtering by subcategory:", subcat);
}

// Store Page Functions
function initStorePage() {
  console.log("Initializing Store Page...");
  if (typeof kakao !== "undefined" && kakao.maps && kakao.maps.services) {
    initKakaoMap();
  } else {
    setTimeout(() => {
      if (typeof kakao !== "undefined" && kakao.maps) initKakaoMap();
    }, 500);
  }
}

// Global variable for map instance
let mapInstance = null;
let psInstance = null;
let infowindowInstance = null;
let markers = [];

function initKakaoMap() {
  const container = document.getElementById("map");
  if (!container) return;

  kakao.maps.load(function () {
    const options = {
      center: new kakao.maps.LatLng(37.566826, 126.9786567),
      level: 5,
    };
    mapInstance = new kakao.maps.Map(container, options);
    psInstance = new kakao.maps.services.Places();
    infowindowInstance = new kakao.maps.InfoWindow({ zIndex: 1 });

    psInstance.keywordSearch("서울 중구 뚜레쥬르", placesSearchCB);
  });
}

window.searchPlaces = function () {
  const keyword = document.getElementById("keyword").value;
  if (!keyword.trim()) {
    alert("키워드를 입력해주세요!");
    return;
  }
  if (!psInstance) {
    alert("지도가 아직 로드되지 않았습니다.");
    return;
  }
  psInstance.keywordSearch(keyword, placesSearchCB);
};

function placesSearchCB(data, status, pagination) {
  if (status === kakao.maps.services.Status.OK) {
    displayPlaces(data);
    displayPagination(pagination);
  } else if (status === kakao.maps.services.Status.ZERO_RESULT) {
    alert("검색 결과가 존재하지 않습니다.");
  } else if (status === kakao.maps.services.Status.ERROR) {
    alert("검색 중 오류가 발생했습니다.");
  }
}

function displayPlaces(places) {
  const listEl = document.getElementById("placesList");
  const menuEl = document.querySelector(".store-list-container");
  const fragment = document.createDocumentFragment();
  const bounds = new kakao.maps.LatLngBounds();

  removeAllChildNods(listEl);
  removeMarker();

  for (let i = 0; i < places.length; i++) {
    const placePosition = new kakao.maps.LatLng(places[i].y, places[i].x);
    const marker = addMarker(placePosition, i);
    const itemEl = getListItem(i, places[i]);

    bounds.extend(placePosition);

    (function (marker, title) {
      kakao.maps.event.addListener(marker, "mouseover", function () {
        displayInfowindow(marker, title);
      });

      kakao.maps.event.addListener(marker, "mouseout", function () {
        infowindowInstance.close();
      });

      itemEl.addEventListener("click", function () {
        displayInfowindow(marker, title);
        mapInstance.setCenter(placePosition);
      });
    })(marker, places[i].place_name);

    fragment.appendChild(itemEl);
  }

  listEl.appendChild(fragment);
  menuEl.scrollTop = 0;
  mapInstance.setBounds(bounds);
}

function getListItem(index, places) {
  const el = document.createElement("li");
  let itemStr = `
        <div class="info">
            <h5>${places.place_name}</h5>
            ${
              places.road_address_name
                ? `<span>${places.road_address_name}</span>
                   <span class="jibun">${places.address_name}</span>`
                : `<span>${places.address_name}</span>`
            }
            <span class="tel">${places.phone}</span>
        </div>
    `;
  el.innerHTML = itemStr;
  el.className = "item";
  return el;
}

function addMarker(position, idx) {
  const imageSrc =
    "https://t1.daumcdn.net/localimg/localimages/07/mapapidoc/marker_number_blue.png";
  const imageSize = new kakao.maps.Size(36, 37);
  const imgOptions = {
    spriteSize: new kakao.maps.Size(36, 691),
    spriteOrigin: new kakao.maps.Point(0, idx * 46 + 10),
    offset: new kakao.maps.Point(13, 37),
  };
  const markerImage = new kakao.maps.MarkerImage(
    imageSrc,
    imageSize,
    imgOptions
  );
  const marker = new kakao.maps.Marker({ position, image: markerImage });
  marker.setMap(mapInstance);
  markers.push(marker);
  return marker;
}

function removeMarker() {
  for (let i = 0; i < markers.length; i++) {
    markers[i].setMap(null);
  }
  markers = [];
}

function displayPagination(pagination) {
  const paginationEl = document.getElementById("pagination");
  const fragment = document.createDocumentFragment();

  while (paginationEl.hasChildNodes()) {
    paginationEl.removeChild(paginationEl.lastChild);
  }

  for (let i = 1; i <= pagination.last; i++) {
    const el = document.createElement("a");
    el.href = "#";
    el.innerHTML = i;

    if (i === pagination.current) {
      el.className = "on";
    } else {
      el.onclick = (function (i) {
        return function () {
          pagination.gotoPage(i);
        };
      })(i);
    }

    fragment.appendChild(el);
  }
  paginationEl.appendChild(fragment);
}

function displayInfowindow(marker, title) {
  const content = '<div style="padding:5px;z-index:1;">' + title + "</div>";
  infowindowInstance.setContent(content);
  infowindowInstance.open(mapInstance, marker);
}

function removeAllChildNods(el) {
  while (el.hasChildNodes()) {
    el.removeChild(el.lastChild);
  }
}

// Event Page Functions
function initEventPage() {
  console.log("Event Page Initialized");
}

window.showEventTab = function (tab) {
  const eventSection = document.getElementById("eventSection");
  const noticeSection = document.getElementById("noticeSection");
  const eventTabBtn = document.getElementById("eventTabBtn");
  const noticeTabBtn = document.getElementById("noticeTabBtn");

  if (tab === "event") {
    eventSection.style.display = "block";
    noticeSection.style.display = "none";

    eventTabBtn.classList.add("active");
    noticeTabBtn.classList.remove("active");
  } else {
    eventSection.style.display = "none";
    noticeSection.style.display = "block";

    eventTabBtn.classList.remove("active");
    noticeTabBtn.classList.add("active");
  }
}

// Product Modal Functions
let currentModalProduct = null;
let currentModalQty = 1;

function initProductPage() {
  console.log("Product Page Initialized");

  const targetCategory = localStorage.getItem("targetProductCategory") || "new";
  localStorage.removeItem("targetProductCategory");

  setTimeout(() => {
    if (typeof window.filterProducts === "function") {
      window.filterProducts(targetCategory);
    }
  }, 100);
}

window.goToProductCategory = function (category) {
  localStorage.setItem("targetProductCategory", category);
  loadPage("products");
};

let currentModalSlide = 0;

window.openProductModalFromCard = function (cardEl) {
  const id = parseInt(cardEl.dataset.id);
  const name = cardEl.dataset.name;
  const price = parseInt(cardEl.dataset.price);
  const image = cardEl.dataset.image;
  const description = cardEl.dataset.description || "";
  const ingredients = cardEl.dataset.ingredients || "";
  let nutrition = {};
  try {
    nutrition = JSON.parse(cardEl.dataset.nutrition || "{}");
  } catch (e) {}

  openProductModal(id, name, price, image, description, ingredients, nutrition);
};

window.openProductModal = function (
  id,
  name,
  price,
  image,
  description,
  ingredients,
  nutrition
) {
  console.log("Opening Modal", { id, name, nutrition });
  currentModalProduct = { id, name, price, image };
  currentModalQty = 1;
  currentModalSlide = 0;

  const modalImg = document.getElementById("modalProductImage");
  const modalName = document.getElementById("modalProductName");
  const modalItemTotal = document.getElementById("modalItemTotal");
  const modalTotal = document.getElementById("modalTotalPrice");
  const modalQty = document.getElementById("modalQty");
  const modalDesc = document.getElementById("modalProductDesc");
  const modal = document.getElementById("productModal");

  if (modalImg) modalImg.src = image;
  if (modalName) modalName.innerText = name;
  if (modalItemTotal) modalItemTotal.innerText = price.toLocaleString() + "원";
  if (modalTotal) modalTotal.innerText = price.toLocaleString() + "원";
  if (modalQty) modalQty.innerText = 1;
  if (modalDesc) modalDesc.innerText = description || "";

  const ingEl = document.getElementById("modalIngredientsText");
  if (ingEl) ingEl.innerText = ingredients || "-";

  if (nutrition && typeof nutrition === "object") {
    const setNut = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.innerText = val || "-";
    };
    setNut("nutCalories", nutrition.calories);
    setNut("nutSugar", nutrition.sugar);
    setNut("nutProtein", nutrition.protein);
    setNut("nutFat", nutrition.fat);
    setNut("nutSodium", nutrition.sodium);
    setNut("nutAllergens", nutrition.allergens);
  }

  goToModalSlide(0);

  if (modal) {
    modal.style.display = "flex";
    modal.classList.add("active");
  }
};

window.goToModalSlide = function (index) {
  currentModalSlide = index;
  const slides = document.querySelectorAll(".modal-slide");
  const dots = document.querySelectorAll(".slide-dot");

  slides.forEach((slide, i) => {
    slide.classList.toggle("active", i === index);
  });
  dots.forEach((dot, i) => {
    dot.classList.toggle("active", i === index);
  });
};

window.changeModalSlide = function (direction) {
  const totalSlides = document.querySelectorAll(".modal-slide").length;
  let newIndex = currentModalSlide + direction;
  if (newIndex < 0) newIndex = totalSlides - 1;
  if (newIndex >= totalSlides) newIndex = 0;
  goToModalSlide(newIndex);
};

window.closeProductModal = function () {
  const modal = document.getElementById("productModal");
  if (modal) {
    modal.style.display = "none";
    modal.classList.remove("active");
  }
};

window.updateModalQty = function (change) {
  let newQty = currentModalQty + change;
  if (newQty < 1) newQty = 1;
  currentModalQty = newQty;
  document.getElementById("modalQty").innerText = newQty;

  if (currentModalProduct) {
    const total = currentModalProduct.price * newQty;
    document.getElementById("modalItemTotal").innerText =
      total.toLocaleString() + "원";
    document.getElementById("modalTotalPrice").innerText =
      total.toLocaleString() + "원";
  }
};

window.addToCartFromModal = function () {
  if (!currentModalProduct) return;

  if (typeof IS_LOGGED_IN !== "undefined" && !IS_LOGGED_IN) {
    if (
      confirm(
        "로그인이 필요한 서비스입니다.\n로그인 페이지로 이동하시겠습니까?"
      )
    ) {
      closeProductModal();
      loadPage("login");
    }
    return;
  }

  const cart = JSON.parse(localStorage.getItem("cart") || "[]");
  const existingItem = cart.find((item) => item.id == currentModalProduct.id);

  if (existingItem) {
    existingItem.quantity = parseInt(existingItem.quantity) + currentModalQty;
  } else {
    cart.push({ ...currentModalProduct, quantity: currentModalQty });
  }
  localStorage.setItem("cart", JSON.stringify(cart));

  if (typeof updateCartBadge === "function") {
    updateCartBadge();
  } else {
    console.error("updateCartBadge function missing");
  }

  // DB에 저장
  saveCartToDB();

  closeProductModal();
  
  // Show cart added modal
  showCartModal();
};

window.buyNowFromModal = function () {
  addToCartFromModal();
  loadPage("cart");
};

// Default Content Generators
function getHomeContent() {
  return '<div class="hero-section"><span class="hero-placeholder">이미지</span></div>';
}
function getProductContent() {
  return "";
}
function getStoreContent() {
  return "";
}
function getEventContent() {
  return "";
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  const urlParams = new URLSearchParams(window.location.search);
  const urlPage = urlParams.get("page");

  if (urlPage) {
    loadPage(urlPage);
  } else {
    // 항상 홈 페이지부터 시작
    loadPage("home");
  }

  updateCartBadge();
  // DB에서 장바구니 불러오기 (로그인 상태면)
  loadCartFromDB();
  
  loadCartFromDB(); // DB에서 장바구니 불러오기
  checkTransparentHeader();
});

// Header Transparency Logic
function checkTransparentHeader() {
  const header = document.querySelector("header");
  if (!header) return;

  if (currentPage === "home") {
    if (window.scrollY < 50) {
      header.classList.add("header-transparent");
    } else {
      header.classList.remove("header-transparent");
    }
  } else {
    header.classList.remove("header-transparent");
  }
}

window.addEventListener("scroll", checkTransparentHeader);

const observer = new MutationObserver(() => {
  checkTransparentHeader();
});
const contentMain = document.getElementById("home_content");
if (contentMain) observer.observe(contentMain, { childList: true });
