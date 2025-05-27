<?php
// Ensure variables are at least set to avoid errors if not passed, though in practice they should always be.
$item_id = $item_id ?? 0;
$item_name = $item_name ?? 'Unknown Item';
$item_description = $item_description ?? '';
$item_price = $item_price ?? '0.00';
$category_name = $category_name ?? 'Uncategorized';
$image_url = $image_url ?? ''; // Path to a default image if empty
$availability = $availability ?? false;

$card_classes = 'menu-item-card';
if (!$availability) {
    $card_classes .= ' unavailable';
}
?>
<div class="<?php echo $card_classes; ?>" data-item-id="<?php echo htmlspecialchars($item_id); ?>">
    <?php if (!empty($image_url) && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_url)): // Basic check if image exists ?>
        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($item_name); ?>" class="menu-item-image">
    <?php else: ?>
        <div class="menu-item-image-placeholder">No Image</div>
    <?php endif; ?>
    
    <div class="menu-item-details">
        <h3 class="menu-item-name"><?php echo htmlspecialchars($item_name); ?></h3>
        <p class="menu-item-category"><?php echo htmlspecialchars($category_name); ?></p>
        <p class="menu-item-description"><?php echo nl2br(htmlspecialchars($item_description)); ?></p>
        <p class="menu-item-price">$<?php echo htmlspecialchars(number_format((float)$item_price, 2)); ?></p>
    </div>
    
    <div class="menu-item-actions">
        <?php if ($availability): ?>
            <button class="add-to-order-btn" data-item-id="<?php echo htmlspecialchars($item_id); ?>">Add to Order</button>
        <?php else: ?>
            <p class="item-unavailable-text">Currently Unavailable</p>
            <button class="add-to-order-btn" data-item-id="<?php echo htmlspecialchars($item_id); ?>" disabled>Add to Order</button>
        <?php endif; ?>
    </div>
</div>
