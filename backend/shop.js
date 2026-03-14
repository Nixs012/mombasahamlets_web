// backend/shop.js
import { showNotification } from './admin.js';

const API_URL = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

// Function to fetch and display products
async function fetchAndDisplayProducts() {
    const productsTableBody = document.querySelector('#shop-tab .admin-table tbody');
    if (!productsTableBody) return;

    // Show loading state
    productsTableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading products...</td></tr>';

    try {
        const response = await fetch(`${API_URL}/products.php`);
        if (!response.ok) throw new Error('Failed to fetch products');
        const products = await response.json();

        productsTableBody.innerHTML = ''; // Clear loading state

        if (!products || products.length === 0) {
            productsTableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:#666;">No products found. Add one using the form above.</td></tr>';
            return;
        }

        const fallbackImagePath = '/mombasahamlets_web/frontend/images/29.jpg';

        products.forEach(product => {
            // Build image URL
            let imageUrl = fallbackImagePath;
            if (product.image_url && product.image_url.trim()) {
                let imgPath = product.image_url.trim();
                imgPath = imgPath.replace(/^\/mombasahamlets_web\//, '');
                imgPath = imgPath.replace(/^\/?frontend\//, '');
                imgPath = imgPath.replace(/^\//, '');
                if (imgPath && imgPath.length > 0) {
                    imageUrl = `/mombasahamlets_web/frontend/${imgPath}`;
                }
            }

            const createdDate = product.created_at ? new Date(product.created_at).toLocaleDateString() : 'N/A';

            const row = `
                <tr>
                    <td>${product.id}</td>
                    <td>
                        <img src="${imageUrl}" alt="${product.name}" class="table-product-image" onerror="this.onerror=null; this.src='${fallbackImagePath}';">
                        ${product.name}
                    </td>
                    <td>${product.category || 'N/A'}</td>
                    <td>KSh ${parseFloat(product.price || 0).toFixed(2)}</td>
                    <td>${product.stock_quantity || 0}</td>
                    <td>${createdDate}</td>
                    <td class="actions">
                        <button class="btn-edit" data-id="${product.id}" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-delete" data-id="${product.id}" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            productsTableBody.insertAdjacentHTML('beforeend', row);
        });

        // Re-attach event listeners after rendering
        document.querySelectorAll('#shop-tab .btn-edit').forEach(btn => {
            btn.addEventListener('click', () => handleEditProduct(products.find(p => p.id == btn.dataset.id)));
        });
        document.querySelectorAll('#shop-tab .btn-delete').forEach(btn => {
            btn.addEventListener('click', () => handleDeleteProduct(btn.dataset.id));
        });

    } catch (error) {
        console.error('Error fetching products:', error);
        productsTableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:#e74c3c;"><i class="fas fa-exclamation-triangle"></i> Failed to load products. Please try again.</td></tr>';
        showNotification('Could not load products.', 'error');
    }
}

// Function to handle form submission for adding/editing a product
async function handleAddProduct(e) {
    e.preventDefault();

    const form = e.target;
    const productId = document.getElementById('product-id').value;

    // Prevent double submission
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn.disabled) return;

    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="loading"></span> Saving...';
    submitBtn.disabled = true;

    const productData = {
        name: document.getElementById('product-name').value,
        description: document.getElementById('product-description').value,
        price: parseFloat(document.getElementById('product-price').value),
        stock_quantity: parseInt(document.getElementById('product-stock').value),
        category: document.getElementById('product-category').value,
        image_url: document.getElementById('product-image-url').value || null
    };

    // Normalize image_url - remove unnecessary prefixes
    if (productData.image_url) {
        productData.image_url = productData.image_url.replace(/^\/mombasahamlets_web\//, '');
        productData.image_url = productData.image_url.replace(/^\/?frontend\//, '');
        productData.image_url = productData.image_url.replace(/^\//, '');
        if (productData.image_url.length === 0) {
            productData.image_url = null;
        }
    }

    const method = productId ? 'PUT' : 'POST';
    const url = productId ? `${API_URL}/products.php?id=${productId}` : `${API_URL}/products.php`;

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(productData)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to save product');
        }

        showNotification(`Product ${productId ? 'updated' : 'added'} successfully!`, 'success');
        form.reset();
        document.getElementById('product-id').value = '';
        const shopTabH3 = document.querySelector('#shop-tab h3');
        if (shopTabH3) shopTabH3.textContent = 'Add/Edit Product';
        // Clear image preview if exists
        const imageInput = document.getElementById('product-image-url');
        if (imageInput) {
            const preview = imageInput.parentNode.querySelector('img');
            if (preview) preview.src = '';
        }
        // Refresh the list after a short delay to ensure database is updated
        setTimeout(() => {
            fetchAndDisplayProducts();
        }, 300);
    } catch (error) {
        console.error('Error saving product:', error);
        showNotification(`Error: ${error.message}`, 'error');
    } finally {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    }
}

// Function to handle editing a product
function handleEditProduct(product) {
    const form = document.getElementById('shop-form');
    document.getElementById('product-id').value = product.id;
    document.getElementById('product-name').value = product.name || '';
    document.getElementById('product-description').value = product.description || '';
    document.getElementById('product-price').value = product.price || '';
    document.getElementById('product-stock').value = product.stock_quantity || '';
    document.getElementById('product-category').value = product.category || '';

    // Set image URL - ensure it's displayed correctly
    let imageUrl = product.image_url || '';
    if (imageUrl) {
        // If it doesn't start with /mombasahamlets_web/, add it for display
        if (!imageUrl.startsWith('/mombasahamlets_web/') && !imageUrl.startsWith('http')) {
            imageUrl = `/mombasahamlets_web/frontend/${imageUrl.replace(/^\/?frontend\//, '')}`;
        }
    }
    document.getElementById('product-image-url').value = imageUrl;

    const shopTabH3 = document.querySelector('#shop-tab h3');
    if (shopTabH3) shopTabH3.textContent = 'Edit Product';
    form.scrollIntoView({ behavior: 'smooth' });
}

// Function to handle deleting a product
async function handleDeleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) return;

    try {
        const response = await fetch(`${API_URL}/products.php?id=${id}`, { method: 'DELETE' });
        if (!response.ok) throw new Error('Failed to delete product');
        showNotification('Product deleted successfully!', 'success');
        await fetchAndDisplayProducts(); // Refresh the list
    } catch (error) {
        console.error('Error deleting product:', error);
        showNotification(`Could not delete product: ${error.message}`, 'error');
    }
}

export function initShop() {
    const shopForm = document.getElementById('shop-form');
    const shopTab = document.getElementById('shop-tab');

    if (shopForm) {
        shopForm.addEventListener('submit', handleAddProduct);

        // Cancel button
        const cancelBtn = shopForm.querySelector('.btn-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                shopForm.reset();
                document.getElementById('product-id').value = '';
                const shopTabH3 = document.querySelector('#shop-tab h3');
                if (shopTabH3) shopTabH3.textContent = 'Add/Edit Product';
                const imageInput = document.getElementById('product-image-url');
                if (imageInput) {
                    const preview = imageInput.parentNode.querySelector('img');
                    if (preview) preview.src = '';
                }
            });
        }
    }

    // Initial fetch if the tab is already active on load
    if (shopTab && shopTab.classList.contains('active')) {
        fetchAndDisplayProducts();
    }

    // Listen for tab changes from admin.js
    window.addEventListener('tabChanged', (e) => {
        if (e.detail.tabName === 'shop') {
            fetchAndDisplayProducts();
        }
    });
}

