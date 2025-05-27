<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Billing System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div id="app">
        <header>
            <h1>Restaurant Name</h1>
            <!-- Navigation could go here if needed -->
        </header>
        <main>
            <section id="billing-interface">
                <!-- Area where menu items are displayed -->
                <div id="menu-display-area">
                    <h2>Menu</h2>
                    <!-- Menu items will be loaded here by JavaScript -->
                    <!-- Example of how a menu item might look (can be a template for JS)
                    <article class="menu-item" data-item-id="1">
                        <h3>Item Name</h3>
                        <p>Price: $10.00</p>
                        <button class="add-to-order-btn">Add to Order</button>
                    </article>
                    -->
                </div>

                <!-- Area displaying the current order -->
                <aside id="current-order-area">
                    <h2>Your Order</h2>
                    <div id="order-items-list">
                        <!-- Order items will appear here, dynamically added by JavaScript -->
                        <!-- Example:
                        <div class="order-item" data-item-id="1">
                            <span>Item Name (x1)</span>
                            <span>$10.00</span>
                            <button class="remove-from-order-btn">Remove</button>
                        </div>
                        -->
                    </div>
                    <div id="order-summary">
                        <p>Subtotal: $<span id="subtotal">0.00</span></p>
                        <p>Tax (e.g., 8%): $<span id="tax">0.00</span></p>
                        <p><strong>Grand Total: $<span id="grand-total">0.00</span></strong></p>
                    </div>
                </aside>
            </section>

            <!-- Action buttons for the billing process -->
            <section id="billing-actions">
                <button id="print-kitchen-btn">Print Kitchen Ticket</button>
                <button id="process-payment-btn">Process Payment</button>
                <button id="clear-order-btn">Clear Order</button>
            </section>
        </main>
        <footer>
            <p>&copy; <?php echo date("Y"); ?> Restaurant Name. All rights reserved.</p>
        </footer>
    </div>

    <!-- Link to the main JavaScript file -->
    <script src="../assets/js/main.js"></script>
</body>
</html>
