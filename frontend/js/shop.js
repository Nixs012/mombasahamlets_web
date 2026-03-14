document.addEventListener('DOMContentLoaded', () => {
    const productsGrid = document.getElementById('productsGrid');
    const categoryLinks = document.querySelectorAll('.category-link');
    const cartItemsList = document.getElementById('cart-items'); // Cart display area
    const cartTotalElement = document.getElementById('cart-total'); // Cart total display
    const sortBySelect = document.getElementById('sort-by');

    let allProducts = []; // Cache for all fetched products
    let cart = []; // Array to hold cart items

    /**
     * Fetches products from the backend API.
     */
    async function fetchProducts() {
        if (!productsGrid) {
            console.error('Products grid not found.');
            return;
        }
        productsGrid.innerHTML = '<div class="loading-spinner"></div><p class="loading-text">Loading Products...</p>';

        try {
            // Use the global API_URL from api-config.js, or fallback to relative path
            const apiUrl = window.API_URL || '/mombasahamlets_web/backend/api';
            const response = await fetch(`${apiUrl}/products.php`);
            if (!response.ok) {
                throw new Error(`API request failed with status ${response.status}`);
            }

            allProducts = await response.json();

            if (!Array.isArray(allProducts)) {
                throw new Error("Received invalid data from the server.");
            }

            // Initial render
            filterAndSortProducts();

            // Load cart from localStorage
            loadCart();

        } catch (error) {
            console.warn('Could not fetch products:', error.message);
            productsGrid.innerHTML = `<div class="shop-error"><h3>The shop is currently unavailable.</h3><p>Please check back later.</p></div>`;
        }
    }

    /**
     * Adds a product to the cart.
     * @param {Object} product - The product to add to the cart.
     * @param {string|null} size - The selected size.
     */
    function addToCart(productOrId, size = null) {
        // Resolve product object if only ID was passed (compatibility with auth-notification.js)
        let product = productOrId;
        if (typeof productOrId === 'number' || typeof productOrId === 'string') {
            const productId = parseInt(productOrId, 10);
            product = allProducts.find(p => p.id == productId);
        }

        if (!product) {
            console.warn('addToCart: Product not found', productOrId);
            return;
        }

        // Check if user is logged in
        if (window.isUserLoggedIn && !window.isUserLoggedIn()) {
            window.showAuthNotification('add-to-cart');
            return;
        }

        // Check if same product + size exists
        const existingItem = cart.find(item => item.id == product.id && item.selectedSize === size);

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            // Get image for cart thumbnail - use relative path
            let imageUrl = 'images/logo1.jpeg';
            if (product.image_url) {
                let imgPath = product.image_url.trim()
                    .replace(/^\/mombasahamlets_web\//, '')
                    .replace(/^\/?frontend\//, '')
                    .replace(/^\//, '');
                if (imgPath) imageUrl = imgPath;
            }

            cart.push({
                ...product,
                selectedSize: size,
                cartImage: imageUrl,
                quantity: 1
            });
        }

        updateCartDisplay();
        saveCart();
        const sizeText = size ? ` (Size: ${size})` : '';
        showNotification(`${product.name}${sizeText} added to cart!`);
    }

    // Export for use by auth-notification.js
    window.addToCart = addToCart;

    /**
     * Removes a product from the cart by index.
     * @param {number} index - The index of the item to remove.
     */
    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCartDisplay();
        saveCart();
    }

    /**
     * Updates the cart display with the current cart items.
     */
    function updateCartDisplay() {
        cartItemsList.innerHTML = '';
        let total = 0;

        cart.forEach((item, index) => {
            const listItem = document.createElement('li');
            listItem.className = 'cart-item';
            const price = item.sale_price ? item.sale_price : item.price;

            // Create a safe container for the cart item
            listItem.innerHTML = `
                    <div class="cart-item-image">
                        <img src="${item.cartImage}" alt="">
                    </div>
                    <div class="cart-item-details">
                        <span class="cart-item-name"></span>
                        <span class="cart-item-size-container"></span>
                        <span class="cart-item-meta">${item.quantity} x KSh ${(price || 0).toFixed(2)}</span>
                    </div>
                    <button class="remove-from-cart" data-index="${index}" aria-label="Remove item"><i class="fas fa-trash"></i></button>
                `;

            // Safely set text content for dynamic data
            listItem.querySelector('.cart-item-name').textContent = item.name;
            const img = listItem.querySelector('img');
            img.alt = item.name;

            if (item.selectedSize) {
                const sizeSpan = document.createElement('span');
                sizeSpan.className = 'cart-item-size';
                sizeSpan.textContent = `(${item.selectedSize})`;
                listItem.querySelector('.cart-item-size-container').appendChild(sizeSpan);
            }

            cartItemsList.appendChild(listItem);
            total += price * item.quantity;
        });

        cartTotalElement.textContent = total.toFixed(2);

        // Update cart count badge
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            const count = cart.reduce((acc, item) => acc + item.quantity, 0);
            cartCount.textContent = count;
            // animate badge
            cartCount.classList.add('bump');
            setTimeout(() => cartCount.classList.remove('bump'), 300);
        }

        // Add event listeners for the remove buttons
        cartItemsList.querySelectorAll('.remove-from-cart').forEach(button => {
            button.addEventListener('click', (e) => {
                const index = parseInt(e.target.closest('button').dataset.index, 10);
                removeFromCart(index);
            });
        });
    }

    /**
     * Saves the cart data to localStorage.
     */
    function saveCart() {
        localStorage.setItem('cart', JSON.stringify(cart));
    }

    /**
     * Loads the cart data from localStorage.
     */
    function loadCart() {
        const storedCart = localStorage.getItem('cart');
        if (storedCart) {
            cart = JSON.parse(storedCart);
            updateCartDisplay();
        }
    }

    /**
     * Displays a temporary notification on the screen.
     * @param {string} message - The message to display.
     * @param {string} type - The type of notification ('success', 'error', 'info').
     */
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `shop-notification notification-${type}`;
        notification.textContent = message;

        // Basic styling
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #2a9d8f;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 99999;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
        `;
        if (type === 'error') {
            notification.style.backgroundColor = '#e76f51';
        }

        document.body.appendChild(notification);

        // Fade in
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(-50%) translateY(-10px)';
        }, 10);

        // Fade out and remove after a delay
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.addEventListener('transitionend', () => notification.remove());
        }, 2500);
    }

    /**
     * Helper to generate size options HTML
     */
    function getProductSizeOptions(product) {
        const category = product.category ? product.category.toLowerCase() : '';
        let sizes = [];

        // Parse sizes from DB string if available
        if (product.sizes) {
            sizes = product.sizes.split(',').map(s => s.trim()).filter(Boolean);
        }

        // Default sizes for specific categories if none provided
        if (sizes.length === 0 && (category.includes('jersey') || category.includes('apparel') || category.includes('shirt'))) {
            sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        }

        if (sizes.length === 0) return '';

        let optionsHtml = '<option value="">Select Size</option>';
        sizes.forEach(size => {
            optionsHtml += `<option value="${size}">${size}</option>`;
        });

        return `
            <div class="size-selector-container">
                <select class="size-select" aria-label="Select Size">
                    ${optionsHtml}
                </select>
            </div>
        `;
    }

    /**
     * Renders an array of product objects into the grid.
     * @param {Array} products - The products to display.
     */
    function renderProducts(products) {
        productsGrid.innerHTML = ''; // Clear spinner

        if (products.length === 0) {
            productsGrid.innerHTML = `<div class="no-products"><h3>No products found</h3><p>Try adjusting your filters.</p></div>`;
            return;
        }

        const fallbackImageUrl = 'images/logo1.jpeg';

        products.forEach(product => {
            const productCard = document.createElement('div');
            productCard.className = 'product-card';
            productCard.dataset.productId = product.id;

            let imageUrl = fallbackImageUrl;

            if (product.image_url && product.image_url.trim()) {
                let imgPath = product.image_url.trim();
                imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
                imgPath = imgPath.replace(/^\/?frontend\//, '');
                imgPath = imgPath.replace(/^\//, '');

                if (imgPath && imgPath.length > 0) {
                    imageUrl = imgPath;
                }
            }

            const isOnSale = product.sale_price && product.sale_price < product.price;

            productCard.innerHTML = `
                <div class="product-image-container">
                    <img src="${imageUrl}" alt="" class="product-image" onerror="this.onerror=null; this.src='${fallbackImageUrl}';">
                    ${isOnSale ? '<span class="sale-badge">Sale</span>' : ''}
                    <div class="product-actions">
                        <button class="action-btn quick-view-btn" aria-label="Quick View" data-product-id="${product.id}"><i class="fas fa-eye"></i></button>
                        <button class="action-btn add-to-cart-btn" aria-label="Add to Cart" data-product-id="${product.id}"><i class="fas fa-shopping-cart"></i></button>
                    </div>
                </div>
                <div class="product-info">
                    <p class="product-category"></p>
                    <h3 class="product-name"></h3>
                    <div class="product-price">
                        ${isOnSale ? `
                            <span class="sale-price">KSh ${product.sale_price.toFixed(2)}</span>
                            <span class="original-price">KSh ${product.price.toFixed(2)}</span>
                        ` : `
                            <span class="regular-price">KSh ${product.price.toFixed(2)}</span>
                        `}
                    </div>
                </div>
            `;

            // Safe assignments
            productCard.querySelector('.product-category').textContent = product.category;
            productCard.querySelector('.product-name').textContent = product.name;
            productCard.querySelector('.product-image').alt = product.name;

            productsGrid.appendChild(productCard);
        });
    }

    /**
     * Filters and sorts the products based on current selections and re-renders the grid.
     */
    function filterAndSortProducts() {
        let processedProducts = [...allProducts];

        // 1. Filter by Category
        const activeCategory = document.querySelector('.category-link.active')?.dataset.category || 'all';
        if (activeCategory !== 'all') {
            processedProducts = processedProducts.filter(p => p.category.trim().toLowerCase() === activeCategory);
        }

        // 2. Sort
        const sortBy = sortBySelect ? sortBySelect.value : 'newest';
        switch (sortBy) {
            case 'price-low':
                processedProducts.sort((a, b) => (a.sale_price || a.price) - (b.sale_price || b.price));
                break;
            case 'price-high':
                processedProducts.sort((a, b) => (b.sale_price || b.price) - (a.sale_price || a.price));
                break;
            case 'newest':
                processedProducts.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                break;
            case 'popularity':
            default:
                break;
        }

        renderProducts(processedProducts);
    }

    // Initial fetch
    fetchProducts();

    // Use event delegation for product actions
    if (productsGrid) {
        productsGrid.addEventListener('click', (e) => {
            const addToCartBtn = e.target.closest('.add-to-cart-btn');
            const quickViewBtn = e.target.closest('.quick-view-btn');

            if (addToCartBtn) {
                e.preventDefault();
                e.stopPropagation();
                const productCard = addToCartBtn.closest('.product-card');
                const productId = parseInt(addToCartBtn.dataset.productId || productCard?.dataset.productId);
                const product = allProducts.find(p => p.id == productId);

                if (product) {
                    const sizeSelect = productCard.querySelector('.size-select');
                    let selectedSize = null;
                    if (sizeSelect) {
                        selectedSize = sizeSelect.value;
                        if (!selectedSize) {
                            alert('Please select a size');
                            return;
                        }
                    }
                    addToCart(product, selectedSize);
                }
                return;
            }

            if (quickViewBtn) {
                e.preventDefault();
                e.stopPropagation();
                if (window.isUserLoggedIn && !window.isUserLoggedIn()) {
                    window.showAuthNotification('quick-view');
                    return;
                }
                const productId = parseInt(quickViewBtn.dataset.productId || quickViewBtn.closest('.product-card')?.dataset.productId);
                const product = allProducts.find(p => p.id == productId);
                if (product) openQuickView(product);
                return;
            }

            const productCard = e.target.closest('.product-card');
            if (productCard) {
                const productId = parseInt(productCard.dataset.productId);
                const product = allProducts.find(p => p.id == productId);
                if (product) openQuickView(product);
            }
        });
    }

    /**
     * Opens the Quick View modal and populates it with product data.
     * @param {Object} product - The product to display.
     */
    function openQuickView(product) {
        const modal = document.getElementById('quickViewModal');
        const modalImg = document.getElementById('modalProductImage');
        const modalName = document.getElementById('modalProductName');
        const modalCategory = document.getElementById('modalProductCategory');
        const modalPrice = document.getElementById('modalProductPrice');
        const modalDesc = document.getElementById('modalProductDesc');
        const modalOptions = document.getElementById('modalProductOptions');
        const modalAddToCartBtn = document.getElementById('modalAddToCart');

        if (!modal) return;

        modalName.textContent = product.name || 'Product';
        modalCategory.textContent = product.category || 'General';
        modalDesc.textContent = product.description || "Official Mombasa Hamlets FC merchandise.";

        let imageUrl = 'images/logo1.jpeg';
        if (product.image_url) {
            let imgPath = product.image_url.trim()
                .replace(/^\/mombasahamlets_web\//, '')
                .replace(/^\/?frontend\//, '')
                .replace(/^\//, '');
            if (imgPath) imageUrl = imgPath;
        }
        modalImg.src = imageUrl;

        const isOnSale = product.sale_price && product.sale_price < product.price;
        modalPrice.innerHTML = isOnSale ? `
            <span class="sale-price">KSh ${product.sale_price.toFixed(2)}</span>
            <span class="original-price">KSh ${product.price.toFixed(2)}</span>
        ` : `
            <span class="regular-price">KSh ${product.price.toFixed(2)}</span>
        `;

        modalOptions.innerHTML = getProductSizeOptions(product);

        modalAddToCartBtn.onclick = () => {
            const sizeSelect = modalOptions.querySelector('.size-select');
            let selectedSize = null;
            if (sizeSelect) {
                selectedSize = sizeSelect.value;
                if (!selectedSize) {
                    alert('Please select a size');
                    return;
                }
            }
            addToCart(product, selectedSize);
            closeQuickViewModal();
        };

        modal.style.display = 'flex';
        setTimeout(() => { modal.classList.add('active'); }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeQuickViewModal() {
        const modal = document.getElementById('quickViewModal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => { modal.style.display = 'none'; }, 300);
            document.body.style.overflow = '';
        }
    }

    window.showQuickView = (productId) => {
        const product = allProducts.find(p => p.id == productId);
        if (product) openQuickView(product);
    };

    const closeBtn = document.getElementById('closeQuickView');
    const modalOverlay = document.getElementById('quickViewModal');
    if (closeBtn) { closeBtn.addEventListener('click', closeQuickViewModal); }
    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeQuickViewModal();
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeQuickViewModal();
    });

    categoryLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            categoryLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            filterAndSortProducts();
        });
    });

    if (sortBySelect) {
        sortBySelect.addEventListener('change', filterAndSortProducts);
    }

    // --- Checkout Logic ---
    const checkoutBtn = document.querySelector('.btn-checkout');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', async () => {
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }

            if (window.isUserLoggedIn && !window.isUserLoggedIn()) {
                window.showAuthNotification('Please login to proceed to checkout');
                return;
            }

            try {
                checkoutBtn.disabled = true;
                checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                const apiUrl = window.API_URL || '/mombasahamlets_web/backend/api';
                const token = localStorage.getItem('userToken');

                const response = await fetch(`${apiUrl}/sync_cart.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cart, token })
                });

                if (!response.ok) throw new Error('Failed to sync cart with server.');

                window.location.href = 'checkout.php';

            } catch (error) {
                console.error('Checkout error:', error);
                alert('There was an error preparing your checkout. Please try again.');
                checkoutBtn.disabled = false;
                checkoutBtn.textContent = 'Proceed to Checkout';
            }
        });
    }
});