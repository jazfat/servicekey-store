<?php

require_once '../includes/init.php';

if (isset($_SESSION['flash_message'])) {
    // Flash message debugging removed for clean code, but logic remains
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$stmt_total_revenue = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status = 'completado'");
$total_revenue = $stmt_total_revenue->fetchColumn() ?? 0;

$current_month = date('m');
$current_year = date('Y');
$stmt_month_revenue = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE order_status = 'completado' AND MONTH(created_at) = ? AND YEAR(created_at) = ?");
$stmt_month_revenue->execute([$current_month, $current_year]);
$month_revenue = $stmt_month_revenue->fetchColumn() ?? 0;

$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

$total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

$perPage_orders = 5;
$currentPage_orders = isset($_GET['page_recent_orders']) ? (int)$_GET['page_recent_orders'] : 1;
$offset_orders = ($currentPage_orders - 1) * $perPage_orders;

try {
    $total_orders_count_stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $total_recent_orders = $total_orders_count_stmt->fetchColumn();
    $pages_recent_orders = ceil($total_recent_orders / $perPage_orders);

    $stmt_recent_orders = $pdo->prepare("SELECT id, customer_email, total_amount, order_status FROM orders ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt_recent_orders->execute([$perPage_orders, $offset_orders]);
    $recent_orders = $stmt_recent_orders->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener órdenes recientes con paginación: " . $e->getMessage());
    $recent_orders = [];
    $pages_recent_orders = 1;
}

$perPage_lowStock = 5;
$currentPage_lowStock = isset($_GET['page_low_stock']) ? (int)$_GET['page_low_stock'] : 1;
$offset_lowStock = ($currentPage_lowStock - 1) * $perPage_lowStock;

try {
    // Corrección SQL para el contador de productos con bajo stock
    $total_low_stock_count_stmt = $pdo->query("
        SELECT COUNT(temp.product_id) FROM (
            SELECT p.id as product_id
            FROM products p
            LEFT JOIN licenses l ON p.id = l.product_id AND l.status = 'disponible'
            WHERE
                (p.is_physical = 1 AND p.allow_preorder = 0)
                OR p.allow_preorder = 1
            GROUP BY p.id, p.name, p.allow_preorder
            HAVING
                COUNT(l.id) <= 5 OR p.allow_preorder = 1
        ) as temp;
    ");
    $total_low_stock_products = $total_low_stock_count_stmt->fetchColumn();
    $pages_lowStock_products = ceil($total_low_stock_products / $perPage_lowStock);

    $stmt_low_stock = $pdo->prepare("
        SELECT p.id, p.name, COUNT(l.id) as stock_disponible, p.allow_preorder
        FROM products p
        LEFT JOIN licenses l ON p.id = l.product_id AND l.status = 'disponible'
        WHERE
            (p.is_physical = 1 AND p.allow_preorder = 0)
            OR p.allow_preorder = 1
        GROUP BY p.id, p.name, p.allow_preorder
        HAVING stock_disponible <= 5 OR p.allow_preorder = 1
        ORDER BY stock_disponible ASC, p.name ASC
        LIMIT ? OFFSET ?
    ");
    $stmt_low_stock->execute([$perPage_lowStock, $offset_lowStock]);
    $low_stock_products = $stmt_low_stock->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al obtener productos con bajo inventario con paginación: " . $e->getMessage());
    $low_stock_products = [];
    $pages_lowStock_products = 1;
}

$stmt_profit = $pdo->query("
    SELECT
        SUM(o.total_amount) as total_revenue,
        SUM(CASE WHEN l.costo_usd IS NOT NULL THEN l.costo_usd ELSE 0 END) as total_cost_of_goods_sold
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN licenses l ON oi.license_id = l.id
    WHERE o.order_status = 'completado'
");
$profit_data = $stmt_profit->fetch(PDO::FETCH_ASSOC);
$total_profit = ($profit_data['total_revenue'] ?? 0) - ($profit_data['total_cost_of_goods_sold'] ?? 0);

$monthly_sales_data = ['labels' => [], 'data' => []];
try {
    $stmt_sales = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(total_amount) as total_sales
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND order_status = 'completado'
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt_sales->execute();
    $raw_sales = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);

    foreach ($raw_sales as $row) {
        $monthly_sales_data['labels'][] = $row['month'];
        $monthly_sales_data['data'][] = (float) $row['total_sales'];
    }

} catch (PDOException $e) {
    error_log("Error al obtener ventas mensuales para el gráfico: " . $e->getMessage());
}

$top_products_data = ['labels' => [], 'data' => []];
try {
    $stmt_top_products = $pdo->prepare("
        SELECT
            p.name,
            SUM(oi.quantity) as total_quantity_sold
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_status = 'completado'
        GROUP BY p.name
        ORDER BY total_quantity_sold DESC
        LIMIT 5
    ");
    $stmt_top_products->execute();
    $raw_top_products = $stmt_top_products->fetchAll(PDO::FETCH_ASSOC);

    foreach ($raw_top_products as $row) {
        $top_products_data['labels'][] = $row['name'];
        $top_products_data['data'][] = (int) $row['total_quantity_sold'];
    }

} catch (PDOException $e) {
    error_log("Error al obtener productos más vendidos para el gráfico: " . $e->getMessage());
}

$page_title = 'Dashboard';
include_once 'admin_header.php';
?>
<?php
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    echo '<div class="' . htmlspecialchars($message['type']) . '-message">' . htmlspecialchars($message['text']) . '</div>';
    unset($_SESSION['flash_message']);
}
?>

<div class="container-admin-panel">
    <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>

    <div class="dashboard-stats-grid">
        <div class="stat-card">
            <i class="bi bi-cash-coin"></i>
            <div class="stat-info">
                <span class="stat-title">Ingresos Totales</span>
                <span class="stat-value">$<?php echo number_format($total_revenue, 2); ?></span>
            </div>
        </div>
        <div class="stat-card">
            <i class="bi bi-calendar-check"></i>
            <div class="stat-info">
                <span class="stat-title">Ventas (Mes Actual)</span>
                <span class="stat-value">$<?php echo number_format($month_revenue, 2); ?></span>
            </div>
        </div>
        <div class="stat-card">
            <i class="bi bi-graph-up-arrow"></i>
            <div class="stat-info">
                <span class="stat-title">Beneficio Neto Total</span>
                <span class="stat-value">$<?php echo number_format($total_profit, 2); ?></span>
            </div>
        </div>
        <div class="stat-card">
            <i class="bi bi-box-seam"></i>
            <div class="stat-info">
                <span class="stat-title">Total de Órdenes</span>
                <span class="stat-value"><?php echo $total_orders; ?></span>
            </div>
        </div>
        <div class="stat-card">
            <i class="bi bi-people-fill"></i>
            <div class="stat-info">
                <span class="stat-title">Clientes Registrados</span>
                <span class="stat-value"><?php echo $total_customers; ?></span>
            </div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-container">
            <h2>Ventas Mensuales (Últimos 6 Meses)</h2>
            <canvas id="monthlySalesChart"></canvas>
            <?php if (empty($monthly_sales_data['labels'])): ?>
                <p class="no-data-message">No hay datos de ventas para mostrar en este momento.</p>
            <?php endif; ?>
        </div>
        <div class="chart-container">
            <h2>Productos Más Vendidos (Cantidad)</h2>
            <canvas id="topProductsChart"></canvas>
            <?php if (empty($top_products_data['labels'])): ?>
                <p class="no-data-message">No hay datos de productos vendidos para mostrar en este momento.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-columns">
        <div class="dashboard-column">
            <h2><i class="bi bi-clock-history"></i> Órdenes Recientes</h2>
            <?php if (empty($recent_orders)): ?>
                <p class="info-message">No hay órdenes recientes para mostrar.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>ID</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php foreach($recent_orders as $order): ?>
                        <tr>
                            <td><a href="<?php echo BASE_URL; ?>admin/view_order.php?id=<?php echo htmlspecialchars($order['id']); ?>">#<?php echo htmlspecialchars($order['id']); ?></a></td>
                            <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <nav class="pagination-admin">
                    <?php for ($i = 1; $i <= $pages_recent_orders; $i++): ?>
                        <a href="?page_recent_orders=<?php echo $i; ?><?php if ($currentPage_lowStock > 1) echo '&page_low_stock=' . $currentPage_lowStock; ?>" class="<?php if ($currentPage_orders === $i) echo 'active'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        </div>
        <div class="dashboard-column">
            <h2><i class="bi bi-exclamation-triangle-fill"></i> Bajo Inventario</h2>
            <?php if (empty($low_stock_products)): ?>
                <p class="info-message">¡Buen trabajo! Todas las licencias tienen un buen nivel de stock.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>Producto</th><th>Disponibles</th></tr></thead>
                    <tbody>
                        <?php foreach($low_stock_products as $product): ?>
                        <tr class="low-stock-row">
                            <td><a href="<?php echo BASE_URL; ?>admin/manage_licenses.php?product_id=<?php echo htmlspecialchars($product['id']); ?>"><?php echo htmlspecialchars($product['name']); ?></a></td>
                            <td><strong><?php echo htmlspecialchars($product['stock_disponible']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <nav class="pagination-admin">
                    <?php for ($i = 1; $i <= $pages_lowStock_products; $i++): ?>
                        <a href="?page_low_stock=<?php echo $i; ?><?php if ($currentPage_orders > 1) echo '&page_recent_orders=' . $currentPage_orders; ?>" class="<?php if ($currentPage_lowStock === $i) echo 'active'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const monthlySalesData = <?php echo json_encode($monthly_sales_data); ?>;
        const topProductsData = <?php echo json_encode($top_products_data); ?>;

        const monthlySalesCanvas = document.getElementById('monthlySalesChart');
        if (monthlySalesCanvas && monthlySalesData.labels.length > 0) {
            const ctxSales = monthlySalesCanvas.getContext('2d');
            new Chart(ctxSales, {
                type: 'line',
                data: {
                    labels: monthlySalesData.labels,
                    datasets: [{
                        label: 'Ventas ($ USD)',
                        data: monthlySalesData.data,
                        backgroundColor: 'rgba(106, 13, 173, 0.4)',
                        borderColor: 'rgba(106, 13, 173, 1)',
                        borderWidth: 1,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Ingresos ($ USD)',
                                color: 'var(--text-color-light)'
                            },
                            ticks: {
                                color: 'var(--text-color-light)'
                            },
                            grid: {
                                color: 'rgba(var(--secondary-color-rgb), 0.3)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Mes',
                                color: 'var(--text-color-light)'
                            },
                            ticks: {
                                color: 'var(--text-color-light)'
                            },
                            grid: {
                                color: 'rgba(var(--secondary-color-rgb), 0.3)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                color: 'var(--text-color)'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        } else if (monthlySalesCanvas) {
            monthlySalesCanvas.style.display = 'none';
        }


        const topProductsCanvas = document.getElementById('topProductsChart');
        if (topProductsCanvas && topProductsData.labels.length > 0) {
            const ctxProducts = topProductsCanvas.getContext('2d');
            new Chart(ctxProducts, {
                type: 'bar',
                data: {
                    labels: topProductsData.labels,
                    datasets: [{
                        label: 'Cantidad Vendida',
                        data: topProductsData.data,
                        backgroundColor: [
                            'var(--primary-color)',
                            'rgba(var(--primary-color-rgb), 0.8)',
                            'rgba(var(--primary-color-rgb), 0.6)',
                            'rgba(var(--primary-color-rgb), 0.4)',
                            'rgba(var(--primary-color-rgb), 0.2)'
                        ],
                        borderColor: [
                            'var(--primary-color)',
                            'var(--primary-color)',
                            'var(--primary-color)',
                            'var(--primary-color)',
                            'var(--primary-color)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Unidades Vendidas',
                                color: 'var(--text-color-light)'
                            },
                            ticks: {
                                color: 'var(--text-color-light)'
                            },
                            grid: {
                                color: 'rgba(var(--secondary-color-rgb), 0.3)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Producto',
                                color: 'var(--text-color-light)'
                            },
                            ticks: {
                                color: 'var(--text-color-light)'
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y;
                                }
                            }
                        }
                    }
                }
            });
        } else if (topProductsCanvas) {
            topProductsCanvas.style.display = 'none';
        }
    });
</script>

<?php require_once 'admin_footer.php'; ?>