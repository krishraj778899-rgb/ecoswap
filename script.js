/* =========================================================
   ECOSWAP - FINAL SCRIPT
   Frontend: HTML + CSS + JavaScript
   Backend: PHP + MySQL
========================================================= */

const API_BASE = "backend/api/";


/* =========================================================
   GLOBAL VARIABLES
========================================================= */

let currentCategory = "All";
let currentSearch = "";


/* =========================================================
   DOM READY
========================================================= */

document.addEventListener("DOMContentLoaded", () => {

    setupMobileMenu();
    setupModals();
    setupForms();
    setupScrollNavigation();

    loadItems();
    updateLoginUI();

});


/* =========================================================
   MOBILE MENU
========================================================= */

function setupMobileMenu() {

    const menuBtn = document.getElementById("menuBtn");
    const navLinks = document.getElementById("navLinks");

    if (!menuBtn || !navLinks) return;

    menuBtn.addEventListener("click", () => {

        navLinks.classList.toggle("active");

        const icon = menuBtn.querySelector("i");

        if (icon) {

            if (navLinks.classList.contains("active")) {
                icon.className = "fas fa-times";
            } else {
                icon.className = "fas fa-bars";
            }

        }

    });


    document.querySelectorAll(".nav-links a").forEach(link => {

        link.addEventListener("click", () => {
            navLinks.classList.remove("active");

            const icon = menuBtn.querySelector("i");

            if (icon) {
                icon.className = "fas fa-bars";
            }
        });

    });

}


/* =========================================================
   MODAL SETUP
========================================================= */

function setupModals() {

    document.querySelectorAll(".modal").forEach(modal => {

        const closeBtn = modal.querySelector(".modal-close");

        if (closeBtn) {

            closeBtn.addEventListener("click", () => {
                closeModal(modal.id);
            });

        }

    });


    window.addEventListener("click", event => {

        document.querySelectorAll(".modal").forEach(modal => {

            if (event.target === modal) {
                closeModal(modal.id);
            }

        });

    });


    document.addEventListener("keydown", event => {

        if (event.key === "Escape") {

            document.querySelectorAll(".modal.active").forEach(modal => {
                closeModal(modal.id);
            });

        }

    });

}


/* =========================================================
   OPEN MODAL
========================================================= */

function openModal(modalId) {

    const modal = document.getElementById(modalId);

    if (!modal) return;

    modal.classList.add("active");

    document.body.style.overflow = "hidden";
}


/* =========================================================
   CLOSE MODAL
========================================================= */

function closeModal(modalId) {

    const modal = document.getElementById(modalId);

    if (!modal) return;

    modal.classList.remove("active");

    document.body.style.overflow = "";
}


/* =========================================================
   SWITCH LOGIN / SIGNUP
========================================================= */

function switchModal(type) {

    if (type === "login") {

        closeModal("signupModal");
        openModal("loginModal");

    }

    if (type === "signup") {

        closeModal("loginModal");
        openModal("signupModal");

    }

}


/* =========================================================
   FORM SETUP
========================================================= */

function setupForms() {

    const loginForm = document.getElementById("loginForm");

    if (loginForm) {

        loginForm.addEventListener("submit", handleLogin);

    }


    const signupForm = document.getElementById("signupForm");

    if (signupForm) {

        signupForm.addEventListener("submit", handleSignup);

    }


    const addItemForm = document.getElementById("addItemForm");

    if (addItemForm) {

        addItemForm.addEventListener("submit", handleAddItem);

    }

}


/* =========================================================
   SIGNUP
========================================================= */

async function handleSignup(event) {

    event.preventDefault();

    const nameInput = document.getElementById("signupName");
    const emailInput = document.getElementById("signupEmail");
    const passwordInput = document.getElementById("signupPassword");

    if (!nameInput || !emailInput || !passwordInput) return;

    const name = nameInput.value.trim();
    const email = emailInput.value.trim();
    const password = passwordInput.value;


    if (!name || !email || !password) {

        showToast("Please fill all fields.", "error");
        return;

    }


    if (password.length < 6) {

        showToast(
            "Password must be at least 6 characters.",
            "error"
        );

        return;

    }


    try {

        const response = await fetch(
            `${API_BASE}register.php`,
            {
                method: "POST",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    password: password
                })
            }
        );


        const data = await response.json();


        if (!response.ok || !data.success) {

            throw new Error(
                data.message || "Registration failed."
            );

        }


        showToast(
            "Account created successfully!",
            "success"
        );


        event.target.reset();

        closeModal("signupModal");

        openModal("loginModal");


    } catch (error) {

        console.error("Signup Error:", error);

        showToast(
            error.message || "Unable to create account.",
            "error"
        );

    }

}


/* =========================================================
   LOGIN
========================================================= */

async function handleLogin(event) {

    event.preventDefault();

    const emailInput = document.getElementById("loginEmail");
    const passwordInput = document.getElementById("loginPassword");

    if (!emailInput || !passwordInput) return;

    const email = emailInput.value.trim();
    const password = passwordInput.value;


    if (!email || !password) {

        showToast(
            "Please enter email and password.",
            "error"
        );

        return;

    }


    try {

        const response = await fetch(
            `${API_BASE}login.php`,
            {
                method: "POST",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            }
        );


        const data = await response.json();


        if (!response.ok || !data.success) {

            throw new Error(
                data.message || "Login failed."
            );

        }


        /* Store user for frontend UI */

        localStorage.setItem(
            "ecoswapUser",
            JSON.stringify(data.user)
        );


        showToast(
            `Welcome ${data.user.name}!`,
            "success"
        );


        event.target.reset();

        closeModal("loginModal");

        updateLoginUI();


    } catch (error) {

        console.error("Login Error:", error);

        showToast(
            error.message || "Unable to login.",
            "error"
        );

    }

}


/* =========================================================
   UPDATE LOGIN UI
========================================================= */

function updateLoginUI() {

    const userData = localStorage.getItem("ecoswapUser");

    const loginButtons = document.querySelectorAll(
        '[onclick*="loginModal"]'
    );

    const signupButtons = document.querySelectorAll(
        '[onclick*="signupModal"]'
    );


    if (!userData) {

        return;

    }


    let user;

    try {

        user = JSON.parse(userData);

    } catch {

        localStorage.removeItem("ecoswapUser");
        return;

    }


    loginButtons.forEach(button => {

        button.textContent = user.name;

        button.removeAttribute("onclick");

    });


    signupButtons.forEach(button => {

        button.textContent = "Logout";

        button.removeAttribute("onclick");

        button.addEventListener("click", logoutUser);

    });

}


/* =========================================================
   LOGOUT
========================================================= */

async function logoutUser() {

    try {

        await fetch(
            `${API_BASE}logout.php`,
            {
                method: "POST",
                credentials: "include"
            }
        );

    } catch (error) {

        console.log("Logout API unavailable.");

    }


    localStorage.removeItem("ecoswapUser");

    showToast(
        "You have been logged out.",
        "success"
    );


    setTimeout(() => {
        window.location.reload();
    }, 700);

}


/* =========================================================
   LOAD ITEMS
========================================================= */

async function loadItems(
    search = currentSearch,
    category = currentCategory
) {

    const container = document.getElementById(
        "itemsContainer"
    );

    if (!container) return;


    container.innerHTML = `
        <p class="no-items">
            Loading items...
        </p>
    `;


    try {

        const params = new URLSearchParams();


        if (search && search.trim() !== "") {

            params.append(
                "search",
                search.trim()
            );

        }


        if (
            category &&
            category.toLowerCase() !== "all"
        ) {

            params.append(
                "category",
                category
            );

        }


        const query = params.toString();


        const url =
            `${API_BASE}items.php` +
            (query ? `?${query}` : "");


        const response = await fetch(
            url,
            {
                method: "GET",
                credentials: "include"
            }
        );


        const data = await response.json();


        if (!response.ok || !data.success) {

            throw new Error(
                data.message || "Unable to load items."
            );

        }


        displayItems(data.items || []);


    } catch (error) {

        console.error("Load Items Error:", error);


        container.innerHTML = `
            <div class="no-items">
                <i class="fas fa-exclamation-circle"></i>
                <p>
                    Unable to load items.
                </p>
                <small>
                    Please check your PHP server and database.
                </small>
            </div>
        `;

    }

}


/* =========================================================
   DISPLAY ITEMS
========================================================= */

function displayItems(items) {

    const container = document.getElementById(
        "itemsContainer"
    );

    if (!container) return;


    if (!items || items.length === 0) {

        container.innerHTML = `
            <div class="no-items">
                <i class="fas fa-box-open"></i>
                <p>No items found.</p>
                <small>
                    Try another search or category.
                </small>
            </div>
        `;

        return;

    }


    container.innerHTML = items.map(item => {

        const imageUrl = item.image
            ? `${API_BASE}${item.image.replace("../", "")}`
            : "";


        const safeName = escapeHTML(
            item.name || "Unnamed Item"
        );

        const safeCategory = escapeHTML(
            item.category || "Others"
        );

        const safeCondition = escapeHTML(
            item.item_condition ||
            item.condition ||
            "Good"
        );

        const safeDescription = escapeHTML(
            item.description || "No description available."
        );

        const safeOwner = escapeHTML(
            item.owner_name || "EcoSwap User"
        );


        const favoriteKey =
            `ecoswap_favorite_${item.id}`;

        const isFavorite =
            localStorage.getItem(favoriteKey) === "true";


        return `
            <div class="item-card">

                <div class="item-image">

                    ${
                        imageUrl
                        ?
                        `<img
                            src="${imageUrl}"
                            alt="${safeName}"
                            onerror="this.style.display='none'"
                        >`
                        :
                        `
                        <div class="item-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                        `
                    }

                    <button
                        class="favorite-btn"
                        onclick="toggleFavorite(${item.id})"
                        aria-label="Favorite"
                    >
                        <i class="${isFavorite ? "fas" : "far"} fa-heart"></i>
                    </button>

                </div>


                <div class="item-content">

                    <span class="item-category">
                        ${safeCategory}
                    </span>

                    <h3>
                        ${safeName}
                    </h3>

                    <p>
                        ${safeDescription}
                    </p>

                    <div class="item-info">
                        <span>
                            <i class="fas fa-user"></i>
                            ${safeOwner}
                        </span>

                        <span class="condition">
                            ${safeCondition}
                        </span>
                    </div>


                    <div class="item-bottom">

                        <button
                            class="view-item-btn"
                            onclick="viewItem(${item.id})"
                        >
                            View Item
                        </button>

                    </div>

                </div>

            </div>
        `;

    }).join("");

}


/* =========================================================
   SEARCH ITEMS
========================================================= */

function searchItems() {

    const input = document.getElementById(
        "searchInput"
    );

    currentSearch = input
        ? input.value.trim()
        : "";


    loadItems(
        currentSearch,
        currentCategory
    );

}


/* =========================================================
   CATEGORY FILTER
========================================================= */

function filterCategory(category) {

    currentCategory = category;


    document.querySelectorAll(
        ".category-btn"
    ).forEach(button => {

        button.classList.remove("active");

    });


    const buttons = document.querySelectorAll(
        ".category-btn"
    );


    buttons.forEach(button => {

        if (
            button.textContent.trim().toLowerCase() ===
            category.toLowerCase()
        ) {

            button.classList.add("active");

        }

    });


    loadItems(
        currentSearch,
        currentCategory
    );

}


/* =========================================================
   VIEW ITEM
========================================================= */

async function viewItem(itemId) {

    if (!itemId) return;


    try {

        const response = await fetch(
            `${API_BASE}items.php`,
            {
                method: "GET",
                credentials: "include"
            }
        );


        const data = await response.json();


        if (!data.success) {
            throw new Error("Unable to load item.");
        }


        const item = data.items.find(
            currentItem =>
                Number(currentItem.id) === Number(itemId)
        );


        if (!item) {

            showToast(
                "Item is no longer available.",
                "error"
            );

            return;

        }


        showItemDetails(item);


    } catch (error) {

        console.error("View Item Error:", error);

        showToast(
            "Unable to open item details.",
            "error"
        );

    }

}


/* =========================================================
   SHOW ITEM DETAILS
========================================================= */

function showItemDetails(item) {

    const modal = document.getElementById(
        "itemDetailsModal"
    );

    if (!modal) {

        showToast(
            "Item selected successfully.",
            "success"
        );

        return;

    }


    const safeName = escapeHTML(
        item.name || "Unnamed Item"
    );

    const safeCategory = escapeHTML(
        item.category || "Others"
    );

    const safeCondition = escapeHTML(
        item.item_condition ||
        item.condition ||
        "Good"
    );

    const safeDescription = escapeHTML(
        item.description ||
        "No description available."
    );

    const safeOwner = escapeHTML(
        item.owner_name ||
        "EcoSwap User"
    );


    const imageUrl = item.image
        ? `${API_BASE}${item.image.replace("../", "")}`
        : "";


    modal.innerHTML = `

        <div class="modal-content modal-large">

            <button
                class="modal-close"
                onclick="closeModal('itemDetailsModal')"
            >
                &times;
            </button>

            <div class="item-details">

                ${
                    imageUrl
                    ?
                    `
                    <div class="item-details-image">
                        <img
                            src="${imageUrl}"
                            alt="${safeName}"
                        >
                    </div>
                    `
                    :
                    `
                    <div class="item-details-image">
                        <i class="fas fa-image"></i>
                    </div>
                    `
                }

                <div class="item-details-content">

                    <span class="item-category">
                        ${safeCategory}
                    </span>

                    <h2>${safeName}</h2>

                    <p>
                        ${safeDescription}
                    </p>

                    <p>
                        <strong>Condition:</strong>
                        ${safeCondition}
                    </p>

                    <p>
                        <strong>Owner:</strong>
                        ${safeOwner}
                    </p>

                    <button
                        class="modal-submit"
                        onclick="requestSwap(${item.id})"
                    >
                        <i class="fas fa-exchange-alt"></i>
                        Request Swap
                    </button>

                </div>

            </div>

        </div>

    `;


    openModal("itemDetailsModal");

}


/* =========================================================
   ADD ITEM
========================================================= */

async function handleAddItem(event) {

    event.preventDefault();


    const userData =
        localStorage.getItem("ecoswapUser");


    if (!userData) {

        showToast(
            "Please login before adding an item.",
            "error"
        );

        closeModal("addItemModal");
        openModal("loginModal");

        return;

    }


    const form = event.target;

    const formData = new FormData(form);


    const imageFile =
        document.getElementById("itemImage");


    if (
        imageFile &&
        imageFile.files &&
        imageFile.files.length > 0
    ) {

        const file = imageFile.files[0];


        if (file.size > 5 * 1024 * 1024) {

            showToast(
                "Image must be less than 5 MB.",
                "error"
            );

            return;

        }


        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];


        if (!allowedTypes.includes(file.type)) {

            showToast(
                "Only JPG, PNG and WEBP images are allowed.",
                "error"
            );

            return;

        }

    }


    try {

        const response = await fetch(
            `${API_BASE}add-item.php`,
            {
                method: "POST",
                credentials: "include",
                body: formData
            }
        );


        const data = await response.json();


        if (!response.ok || !data.success) {

            throw new Error(
                data.message || "Unable to add item."
            );

        }


        showToast(
            "Item added successfully!",
            "success"
        );


        form.reset();

        closeModal("addItemModal");


        currentSearch = "";
        currentCategory = "All";


        const searchInput =
            document.getElementById("searchInput");

        if (searchInput) {
            searchInput.value = "";
        }


        loadItems();


    } catch (error) {

        console.error("Add Item Error:", error);

        showToast(
            error.message ||
            "Unable to add item.",
            "error"
        );

    }

}


/* =========================================================
   REQUEST SWAP
========================================================= */

async function requestSwap(itemId) {

    const userData =
        localStorage.getItem("ecoswapUser");


    if (!userData) {

        showToast(
            "Please login before requesting a swap.",
            "error"
        );

        closeModal("itemDetailsModal");
        openModal("loginModal");

        return;

    }


    const message =
        prompt(
            "Enter a message for the item owner (optional):"
        );


    if (message === null) {
        return;
    }


    try {

        const response = await fetch(
            `${API_BASE}swap-request.php`,
            {
                method: "POST",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    item_id: Number(itemId),
                    message: message.trim()
                })
            }
        );


        const data = await response.json();


        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Unable to send swap request."
            );

        }


        closeModal("itemDetailsModal");


        showToast(
            "Swap request sent successfully!",
            "success"
        );


    } catch (error) {

        console.error(
            "Swap Request Error:",
            error
        );


        showToast(
            error.message ||
            "Unable to send swap request.",
            "error"
        );

    }

}


/* =========================================================
   FAVORITES
========================================================= */

function toggleFavorite(itemId) {

    const key =
        `ecoswap_favorite_${itemId}`;


    const current =
        localStorage.getItem(key) === "true";


    localStorage.setItem(
        key,
        String(!current)
    );


    showToast(
        !current
            ? "Added to favorites."
            : "Removed from favorites.",
        "success"
    );


    loadItems(
        currentSearch,
        currentCategory
    );

}


/* =========================================================
   TOAST
========================================================= */

function showToast(message, type = "success") {

    const toast =
        document.getElementById("toast");


    if (!toast) {

        alert(message);
        return;

    }


    toast.textContent = message;


    toast.classList.remove(
        "show",
        "success",
        "error"
    );


    toast.classList.add(type);


    setTimeout(() => {

        toast.classList.add("show");

    }, 10);


    setTimeout(() => {

        toast.classList.remove("show");

    }, 3500);

}


/* =========================================================
   SCROLL NAVIGATION
========================================================= */

function setupScrollNavigation() {

    document.querySelectorAll(
        'a[href^="#"]'
    ).forEach(link => {

        link.addEventListener(
            "click",
            event => {

                const targetId =
                    link.getAttribute("href");


                if (
                    !targetId ||
                    targetId === "#"
                ) {
                    return;
                }


                const target =
                    document.querySelector(
                        targetId
                    );


                if (!target) return;


                event.preventDefault();


                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

            }
        );

    });

}


/* =========================================================
   HTML ESCAPE
========================================================= */

function escapeHTML(value) {

    const div =
        document.createElement("div");


    div.textContent =
        value == null
            ? ""
            : String(value);


    return div.innerHTML;

}


/* =========================================================
   GLOBAL FUNCTIONS
   Required for HTML onclick=""
========================================================= */

window.openModal = openModal;
window.closeModal = closeModal;
window.switchModal = switchModal;

window.searchItems = searchItems;
window.filterCategory = filterCategory;

window.viewItem = viewItem;
window.requestSwap = requestSwap;

window.toggleFavorite = toggleFavorite;

window.logoutUser = logoutUser;
window.showToast = showToast;


/* =========================================================
   END
========================================================= */