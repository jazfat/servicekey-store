<?php
/**
 * includes/page_meta.php
 * Genera los datos estructurados JSON-LD para el SEO del producto.
 * Asume que las variables $product, $site_url, y $available_licenses ya existen.
 */
?>
<?php if (isset($product) && !empty($product)): ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "<?php echo htmlspecialchars($product['name']); ?>",
  "image": "<?php echo $site_url . '/' . htmlspecialchars($product['image_url']); ?>",
  "description": "<?php echo htmlspecialchars(strip_tags($product['description'])); ?>",
  "sku": "<?php echo $product['id']; ?>",
  <?php if (!empty($product['brand'])): ?>
  "brand": {
    "@type": "Brand",
    "name": "<?php echo htmlspecialchars($product['brand']); ?>"
  },
  <?php endif; ?>
  "offers": {
    "@type": "Offer",
    "url": "<?php echo $site_url . '/producto.php?id=' . $product['id']; ?>",
    "priceCurrency": "USD",
    "price": "<?php echo $product['price_usd']; ?>",
    "priceValidUntil": "<?php echo date('Y-m-d', strtotime('+1 year')); ?>",
    "availability": "<?php echo ($available_licenses > 0 || $product['allow_preorder']) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'; ?>"
  }
}
</script>
<?php endif; ?>