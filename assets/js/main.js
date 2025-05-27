document.addEventListener('DOMContentLoaded', function() {
    const menuDisplayArea = document.getElementById('menu-display-area');

    function fetchAndDisplayMenuItems() {
        // Path from public/index.php (which includes this script) to app/menu/get_items.php
        // main.js is in ROOT/assets/js/
        // get_items.php is in ROOT/app/menu/
        // index.php is in ROOT/public/
        // Script tag in index.php is src="../assets/js/main.js"
        // So, the base URL for fetch() from main.js is effectively ROOT/assets/js/
        // To get to ROOT/app/menu/get_items.php, it's '../../app/menu/get_items.php'
        fetch('../../app/menu/get_items.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // The PHP script returns {success: true, data: itemsArray}
                // or {success: false, message: '...'}
                if (data.success && data.data && data.data.length > 0) {
                    menuDisplayArea.innerHTML = ''; // Clear loading or previous items
                    data.data.forEach(item => { // Access items via data.data
                        const card = createMenuItemCard(item);
                        menuDisplayArea.appendChild(card);
                    });
                } else if (data.success && (!data.data || data.data.length === 0)) {
                    menuDisplayArea.innerHTML = '<p>No menu items available at the moment.</p>';
                } else {
                    // Handle cases where data.success might be false or undefined
                    menuDisplayArea.innerHTML = `<p>Error loading menu: ${data.message || 'Unknown error'}</p>`;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                menuDisplayArea.innerHTML = `<p>Could not load menu. Client-side error: ${error.message}. Check console for more details.</p>`;
            });
    }

    function createMenuItemCard(item) {
        const card = document.createElement('div');
        card.className = 'menu-item-card';
        card.dataset.itemId = item.item_id; // item.item_id from database

        if (!item.availability) { // item.availability from database (should be boolean or 0/1)
            card.classList.add('unavailable');
        }

        // Image: image_url is expected to be like 'assets/images/item.jpg'
        // The HTML is inserted into public/index.php.
        // So, if image_url is 'assets/images/item.jpg', then src should be '../assets/images/item.jpg'
        let imageHtml = '';
        if (item.image_url) {
            // Assuming item.image_url is relative to the project root, e.g., "assets/images/burger.jpg"
            // The path in HTML (public/index.php) needs to be relative to public/index.php
            imageHtml = `<img src="../${item.image_url}" alt="${item.name}" class="menu-item-image">`;
        } else {
            imageHtml = `<div class="menu-item-image-placeholder">No Image</div>`;
        }

        card.innerHTML = `
            ${imageHtml}
            <div class="menu-item-details">
                <h3 class="menu-item-name">${item.name}</h3>
                <p class="menu-item-category">${item.category_name || 'Uncategorized'}</p>
                <p class="menu-item-description">${item.description || ''}</p>
                <p class="menu-item-price">$${parseFloat(item.price).toFixed(2)}</p>
            </div>
            <div class="menu-item-actions">
                <button class="add-to-order-btn" data-item-id="${item.item_id}" ${!item.availability ? 'disabled' : ''}>
                    ${item.availability ? 'Add to Order' : 'Unavailable'}
                </button>
            </div>
        `;
        return card;
    }

    // Function to add item to order (to be implemented later)
    function addToOrder(itemId) {
        console.log("Adding item to order:", itemId);
        // Placeholder for future logic:
        // 1. Add item to a client-side order object/array
        // 2. Update the #current-order-area in the DOM
        // 3. Recalculate subtotal, tax, and grand total
    }

    // Event delegation for "Add to Order" buttons
    // Ensure menuDisplayArea is not null before adding event listener
    if (menuDisplayArea) {
        menuDisplayArea.addEventListener('click', function(event) {
            if (event.target && event.target.classList.contains('add-to-order-btn')) {
                const itemId = event.target.dataset.itemId;
                if (!event.target.disabled) {
                    addToOrder(itemId);
                }
            }
        });
    } else {
        console.error("#menu-display-area not found. Cannot attach 'Add to Order' event listener.");
    }

    // Initial fetch
    if (menuDisplayArea) { // Only fetch if the area exists
        fetchAndDisplayMenuItems();
    } else {
        console.error("#menu-display-area not found. Cannot display menu items.");
    }
});
