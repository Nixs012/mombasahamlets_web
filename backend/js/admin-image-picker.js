(function () {
    // Small admin image picker that attaches to inputs with class 'image-input'
    // Define a base path for images, which is crucial for local development environments like WAMP.
    const IMAGE_BASE_PATH = '/mombasahamlets_web/frontend/'; // This is for WAMP/MAMP

    async function fetchImages(page = 1, limit = 32) {
        try {
            const resp = await fetch(`${window.API_URL || '/backend/api'}/list-images.php?page=${page}&limit=${limit}`);
            if (!resp.ok) return { images: [], total: 0, pages: 1 };
            return await resp.json();
        } catch (e) {
            console.error('Fetch images error:', e);
            return { images: [], total: 0, pages: 1 };
        }
    }

    function createModal() {
        if (document.getElementById('adminImagePickerModal')) return document.getElementById('adminImagePickerModal');
        const modal = document.createElement('div');
        modal.id = 'adminImagePickerModal';
        modal.style.cssText = `
            position: fixed; left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7); z-index: 20000; display: none;
            overflow-y: auto; padding: 20px; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        `;
        modal.innerHTML = `
            <div style="max-width:1200px;margin:20px auto;background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.3);overflow:hidden;">
                <div style="background:#4361ee;color:#fff;padding:20px;display:flex;justify-content:space-between;align-items:center;">
                    <h2 style="margin:0;font-size:24px;font-weight:600;">Image Manager</h2>
                    <button id="adminImagePickerClose" style="background:rgba(255,255,255,0.2);border:none;color:#fff;font-size:24px;width:36px;height:36px;border-radius:50%;cursor:pointer;transition:background 0.2s;display:flex;align-items:center;justify-content:center;">✕</button>
                </div>
                <div style="padding:24px;">
                    <div style="display:grid;grid-template-columns:280px 1fr;gap:24px;">
                        <div style="border-right:1px solid #eee;padding-right:24px;">
                            <h3 style="margin:0 0 16px 0;font-size:18px;color:#333;">Upload New Image</h3>
                            <form id="adminImageUploadForm" style="margin-bottom:20px;">
                                <div style="margin-bottom:12px;">
                                    <input type="file" name="image" accept="image/*,video/*" id="adminImageFileInput" style="width:100%;padding:8px;border:2px dashed #ddd;border-radius:6px;cursor:pointer;transition:border-color 0.2s;box-sizing:border-box;">
                                </div>
                                <button type="submit" class="btn" id="adminImageUploadBtn" style="width:100%;background:#4361ee;color:#fff;border:none;padding:12px;border-radius:6px;cursor:pointer;font-weight:600;transition:background 0.2s;">Upload Image</button>
                                <div id="adminImageUploadMsg" style="margin-top:12px;padding:8px;border-radius:4px;font-size:13px;min-height:20px;text-align:center;"></div>
                            </form>
                            <div style="padding-top:20px;border-top:1px solid #eee;">
                                <button id="adminImageClearAllBtn" style="width:100%;background:#dc3545;color:#fff;border:none;padding:12px;border-radius:6px;cursor:pointer;font-weight:600;transition:all 0.2s;margin-bottom:20px;">Clear All Uploads</button>
                                <div style="font-size:13px;color:#666;line-height:1.6;background:#f8f9fa;padding:12px;border-radius:6px;">
                                    <strong>Tips:</strong><br>
                                    • Click an image to select it<br>
                                    • Hover images for individual delete<br>
                                    • Clear All removes ONLY user uploads
                                </div>
                            </div>
                        </div>
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                                <h3 style="margin:0;font-size:18px;color:#333;">Existing Library</h3>
                                <div id="adminImageCount" style="font-size:14px;color:#666;font-weight:600;"></div>
                            </div>
                            <div style="margin-bottom:16px;">
                                <input type="search" id="adminImageSearch" placeholder="🔍 Search images by name..." style="width:100%;padding:12px;box-sizing:border-box;border:2px solid #eee;border-radius:8px;font-size:14px;transition:border-color 0.2s;outline:none;">
                            </div>
                            <div id="adminImageList" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:16px;max-height:500px;min-height:300px;overflow-y:auto;padding:8px;background:#f8f9fa;border-radius:8px;align-content:start;"></div>
                            <div style="margin-top:20px;text-align:center;">
                                <button id="adminImageLoadMore" style="background:#fff;border:2px solid #4361ee;color:#4361ee;padding:10px 24px;border-radius:24px;cursor:pointer;font-weight:600;transition:all 0.2s;display:none;">Load More</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }

    async function openPicker(onSelect) {
        const modal = createModal();
        const list = modal.querySelector('#adminImageList');
        const countEl = modal.querySelector('#adminImageCount');
        const loadMoreBtn = modal.querySelector('#adminImageLoadMore');
        const clearAllBtn = modal.querySelector('#adminImageClearAllBtn');
        const searchInput = modal.querySelector('#adminImageSearch');
        const uploadForm = modal.querySelector('#adminImageUploadForm');
        const uploadMsg = modal.querySelector('#adminImageUploadMsg');
        const closeBtn = modal.querySelector('#adminImagePickerClose');

        let currentPage = 1;
        const limitPerPage = 32;
        let allFetchedImages = [];

        function close() { modal.style.display = 'none'; }
        function show() { modal.style.display = 'block'; }

        closeBtn.onclick = close;
        modal.onclick = (e) => { if (e.target === modal) close(); };

        clearAllBtn.onclick = async () => {
            if (!confirm("⚠️ This will permanently delete ALL images you have uploaded. Are you sure?")) return;
            clearAllBtn.disabled = true;
            clearAllBtn.textContent = 'Clearing...';
            try {
                const resp = await fetch(`${window.API_URL || '/backend/api'}/upload-image.php`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ clear_all: true })
                });
                if (resp.ok) {
                    list.innerHTML = '';
                    allFetchedImages = [];
                    countEl.textContent = '0 images';
                    loadMoreBtn.style.display = 'none';
                    uploadMsg.textContent = 'All images cleared.';
                    uploadMsg.style.color = '#28a745';
                } else {
                    alert("Failed to clear images.");
                }
            } catch (err) {
                alert("Error: " + err.message);
            } finally {
                clearAllBtn.disabled = false;
                clearAllBtn.textContent = 'Clear All Uploads';
            }
        };

        async function renderImages(append = false) {
            if (!append) {
                list.innerHTML = '<div style="grid-column:1/-1;padding:40px;text-align:center;color:#666;">Loading...</div>';
                currentPage = 1;
                allFetchedImages = [];
            }

            const data = await fetchImages(currentPage, limitPerPage);
            if (!append) list.innerHTML = '';

            if (!data.images || data.images.length === 0) {
                if (!append) list.innerHTML = '<div style="grid-column:1/-1;padding:40px;text-align:center;color:#999;">No images found.</div>';
                loadMoreBtn.style.display = 'none';
                return;
            }

            data.images.forEach(p => {
                allFetchedImages.push(p);
                const card = document.createElement('div');
                card.className = 'admin-image-card';
                card.style.cssText = `
                    position: relative; cursor: pointer; background: #fff; border: 2px solid #e0e0e0;
                    border-radius: 8px; overflow: hidden; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                `;
                card.dataset.imagePath = p;
                card.dataset.imageName = p.split('/').pop().toLowerCase();

                const imgContainer = document.createElement('div');
                imgContainer.style.cssText = `width: 100%; height: 120px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;`;

                const ext = p.split('.').pop().toLowerCase();
                const isVideo = ['mp4', 'webm', 'ogg', 'mov'].includes(ext);

                if (isVideo) {
                    imgContainer.innerHTML = '<div style="font-size: 32px;">🎥</div><div style="font-size: 10px; position:absolute; bottom:5px; background:rgba(0,0,0,0.5); color:#fff; padding:2px 5px; border-radius:3px;">VIDEO</div>';
                } else {
                    const img = document.createElement('img');
                    // Use _thumb variant for browsing if it exists
                    const thumbPath = p.includes('images/uploads/') ? p.replace(/(\.[a-z0-9]+)$/i, '_thumb$1') : p;
                    img.src = `${IMAGE_BASE_PATH}${thumbPath.replace(/^\//, '')}`;
                    img.style.cssText = `width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;`;
                    img.onerror = () => {
                        img.src = `${IMAGE_BASE_PATH}${p.replace(/^\//, '')}`;
                        img.onerror = null;
                    };
                    imgContainer.appendChild(img);
                }
                card.appendChild(imgContainer);

                const fileName = p.split('/').pop();
                const label = document.createElement('div');
                label.textContent = fileName.length > 15 ? fileName.substring(0, 12) + '...' : fileName;
                label.title = fileName;
                label.style.cssText = `padding: 8px; font-size: 11px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-top: 1px solid #eee; background: #fff;`;
                card.appendChild(label);

                // Hover effects
                card.addEventListener('mouseenter', () => {
                    card.style.borderColor = '#4361ee';
                    card.style.transform = 'translateY(-3px)';
                    card.style.boxShadow = '0 6px 12px rgba(67, 97, 238, 0.15)';
                });
                card.addEventListener('mouseleave', () => {
                    card.style.borderColor = '#e0e0e0';
                    card.style.transform = 'translateY(0)';
                    card.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
                });

                // Deletion button for uploads
                if (p.includes('images/uploads/')) {
                    const del = document.createElement('button');
                    del.innerHTML = '✕';
                    del.style.cssText = `position:absolute; top:5px; right:5px; background:rgba(220,53,69,0.9); color:#fff; border:none; border-radius:50%; width:22px; height:22px; cursor:pointer; opacity:0; transition:opacity 0.2s; z-index:5; font-size:12px; font-weight:bold;`;
                    card.addEventListener('mouseenter', () => del.style.opacity = '1');
                    card.addEventListener('mouseleave', () => del.style.opacity = '0');
                    del.onclick = async (e) => {
                        e.stopPropagation();
                        if (!confirm("Delete this image?")) return;
                        try {
                            const resp = await fetch(`${window.API_URL || '/backend/api'}/upload-image.php`, {
                                method: 'DELETE',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ path: p })
                            });
                            if (resp.ok) {
                                card.remove();
                                data.total--;
                                countEl.textContent = `${data.total} images`;
                            }
                        } catch (err) { alert(err.message); }
                    };
                    card.appendChild(del);
                }

                card.onclick = () => { onSelect(p); close(); };
                list.appendChild(card);
            });

            countEl.textContent = `${data.total} images`;
            loadMoreBtn.style.display = (currentPage < data.pages) ? 'inline-block' : 'none';
        }

        loadMoreBtn.onclick = () => {
            currentPage++;
            renderImages(true);
        };

        searchInput.oninput = () => {
            const term = searchInput.value.toLowerCase();
            const cards = list.querySelectorAll('.admin-image-card');
            let visible = 0;
            cards.forEach(c => {
                const matches = c.dataset.imageName.includes(term);
                c.style.display = matches ? 'block' : 'none';
                if (matches) visible++;
            });
            countEl.textContent = term ? `${visible} matches` : `${allFetchedImages.length} images`;
        };

        uploadForm.onsubmit = async (e) => {
            e.preventDefault();
            const fileInput = uploadForm.querySelector('#adminImageFileInput');
            if (!fileInput.files[0]) return;

            const btn = uploadForm.querySelector('#adminImageUploadBtn');
            btn.disabled = true;
            btn.textContent = 'Uploading...';
            uploadMsg.textContent = 'Processing...';

            const formData = new FormData();
            formData.append('image', fileInput.files[0]);
            try {
                const resp = await fetch(`${window.API_URL || '/backend/api'}/upload-image.php`, { method: 'POST', body: formData });
                if (resp.ok) {
                    uploadMsg.textContent = 'Success!';
                    uploadMsg.style.color = '#28a745';
                    fileInput.value = '';
                    renderImages(false);
                } else {
                    const errorData = await resp.json();
                    uploadMsg.textContent = errorData.error || 'Upload failed';
                    uploadMsg.style.color = '#dc3545';
                }
            } catch (err) {
                uploadMsg.textContent = err.message;
                uploadMsg.style.color = '#dc3545';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Upload Image';
            }
        };

        await renderImages(false);
        show();
    }

    function init() {
        document.querySelectorAll('.image-input').forEach(input => {
            if (input.dataset.pickerAttached) return;
            input.dataset.pickerAttached = '1';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline';
            btn.textContent = 'Browse';
            btn.style.marginLeft = '8px';
            input.parentNode.insertBefore(btn, input.nextSibling);

            const preview = document.createElement('img');
            preview.style.cssText = 'width:60px; height:45px; object-fit:cover; margin-left:10px; vertical-align:middle; border-radius:4px; border:1px solid #ddd; display:none;';
            preview.onerror = () => { preview.style.display = 'none'; };

            const updatePreview = (path) => {
                if (!path || !path.trim()) { preview.style.display = 'none'; return; }
                const cleanPath = path.trim().replace(/^\//, '');
                const isVideo = ['mp4', 'webm', 'ogg', 'mov'].includes(cleanPath.split('.').pop().toLowerCase());
                if (isVideo) {
                    preview.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjNTU1IiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHJlY3QgeD0iMiIgeT0iMiIgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIiByeD0iMi4xOCIgcnk9IjIuMTgiPjwvcmVjdD48bGluZSB4MT0iNyIgeTE9IjIiIHgyPSI3IiB5Mj0iMjIiPjwvbGluZT48bGluZSB4MT0iMTciIHkxPSIyIiB4Mj0iMTciIHkyPSIyMiI+PC9saW5lPjxsaW5lIHgxPSIyIiB5MT0iMTIiIHgyPSIyMiIgeTI9IjEyIj48L2xpbmU+PHBhdGggZD0iTTIgN2g1Ij48L3BhdGg+PHBhdGggZD0iTTIgMTdoNSI+PC9wYXRoPjxwYXRoIGQ9Ik0xNyA3aDUiPjwvcGF0aD48cGF0aCBkPSJNMTcgMTdoNSI+PC9wYXRoPjwvc3ZnPg==';
                } else {
                    preview.src = `${IMAGE_BASE_PATH}${cleanPath}`;
                }
                preview.style.display = 'inline-block';
            };

            updatePreview(input.value);
            input.parentNode.insertBefore(preview, btn.nextSibling);

            btn.onclick = () => {
                openPicker((path) => {
                    input.value = path;
                    updatePreview(path);
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            };

            input.addEventListener('change', () => updatePreview(input.value));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.AdminImagePicker = { open: openPicker, init };
})();
