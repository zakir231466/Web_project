<?php
/**
 * Reusable UI Components
 * Helper functions to generate common UI elements
 */

/**
 * Button Component
 * @param string $text Button text
 * @param string $href URL (for link buttons)
 * @param string $type primary|secondary|success|danger|warning|info
 * @param string $size sm|md|lg
 * @param array $attributes Additional HTML attributes
 */
function button($text, $href = null, $type = 'primary', $size = 'md', $attributes = []) {
    $class = "btn btn-{$type} btn-{$size}";
    $attrs = '';
    foreach ($attributes as $key => $value) {
        $attrs .= " {$key}=\"{$value}\"";
    }
    
    if ($href) {
        return "<a href=\"{$href}\" class=\"{$class}\"{$attrs}>{$text}</a>";
    } else {
        return "<button type=\"button\" class=\"{$class}\"{$attrs}>{$text}</button>";
    }
}

/**
 * Card Component
 * @param string $content Card content
 * @param string|null $title Card title
 * @param string|null $footer Card footer
 * @param string|null $image Image URL
 */
function card($content, $title = null, $footer = null, $image = null) {
    $html = '<div class="card">';
    
    if ($image) {
        $html .= "<img src=\"{$image}\" alt=\"Card image\" class=\"card-image\">";
    }
    
    if ($title) {
        $html .= "<div class=\"card-header\"><h3>{$title}</h3></div>";
    }
    
    $html .= "<div class=\"card-body\">{$content}</div>";
    
    if ($footer) {
        $html .= "<div class=\"card-footer\">{$footer}</div>";
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Badge Component
 * @param string $text Badge text
 * @param string $type primary|success|danger|warning|info|neutral
 */
function badge($text, $type = 'primary') {
    return "<span class=\"badge badge-{$type}\">{$text}</span>";
}

/**
 * Status Badge Component
 * @param string $status Status value
 * @param array $statusMap Mapping of status to display value
 */
function statusBadge($status, $statusMap = []) {
    $default = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'suspended' => 'Suspended'
    ];
    
    $map = array_merge($default, $statusMap);
    $display = $map[$status] ?? ucfirst($status);
    
    return "<span class=\"status-badge status-{$status}\"><span class=\"status-dot\"></span> {$display}</span>";
}

/**
 * Alert Component
 * @param string $message Alert message
 * @param string $type success|danger|warning|info
 * @param bool $dismissible Show close button
 */
function alert($message, $type = 'info', $dismissible = true) {
    $icon = ['success' => '✓', 'danger' => '✕', 'warning' => '⚠', 'info' => 'ℹ'][$type] ?? 'ℹ';
    
    $html = "<div class=\"alert alert-{$type}\">";
    $html .= "<span class=\"alert-icon\">{$icon}</span>";
    $html .= "<div class=\"alert-content\"><p>{$message}</p></div>";
    
    if ($dismissible) {
        $html .= "<button type=\"button\" class=\"alert-close\" onclick=\"this.parentElement.remove()\">&times;</button>";
    }
    
    $html .= "</div>";
    return $html;
}

/**
 * Form Group Component
 * @param string $label Field label
 * @param string $name Field name
 * @param string $type Field type (text|email|password|number|date|textarea|select)
 * @param mixed $value Current value
 * @param array $options Options for select fields
 * @param array $attributes Additional attributes
 */
function formGroup($label, $name, $type = 'text', $value = '', $options = [], $attributes = []) {
    $attrs = '';
    foreach ($attributes as $key => $val) {
        $attrs .= " {$key}=\"{$val}\"";
    }
    
    $html = '<div class="form-group">';
    $html .= "<label for=\"{$name}\">{$label}</label>";
    
    if ($type === 'textarea') {
        $html .= "<textarea id=\"{$name}\" name=\"{$name}\"{$attrs}>" . e($value) . "</textarea>";
    } elseif ($type === 'select') {
        $html .= "<select id=\"{$name}\" name=\"{$name}\"{$attrs}>";
        $html .= "<option value=\"\">-- Select {$label} --</option>";
        foreach ($options as $optValue => $optLabel) {
            $selected = ($optValue == $value) ? 'selected' : '';
            $html .= "<option value=\"{$optValue}\" {$selected}>{$optLabel}</option>";
        }
        $html .= "</select>";
    } else {
        $html .= "<input type=\"{$type}\" id=\"{$name}\" name=\"{$name}\" value=\"" . e($value) . "\"{$attrs}>";
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Table Component
 * @param array $headers Table headers
 * @param array $rows Table rows (each row is associative array)
 * @param array $actions Action buttons (key => ['label' => 'text', 'url' => 'link', 'class' => 'btn-primary'])
 */
function table($headers, $rows, $actions = []) {
    $html = '<div class="table-container"><table class="table">';
    
    // Headers
    $html .= '<thead><tr>';
    foreach ($headers as $header) {
        $html .= "<th>{$header}</th>";
    }
    if (!empty($actions)) {
        $html .= '<th>Actions</th>';
    }
    $html .= '</tr></thead>';
    
    // Body
    $html .= '<tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= "<td>" . e($cell) . "</td>";
        }
        if (!empty($actions)) {
            $html .= '<td class="gap-sm flex">';
            foreach ($actions as $key => $action) {
                $url = str_replace('{id}', $row['id'] ?? '', $action['url']);
                $class = $action['class'] ?? 'btn-primary';
                $html .= "<a href=\"{$url}\" class=\"btn btn-sm {$class}\">{$action['label']}</a>";
            }
            $html .= '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    
    $html .= '</table></div>';
    return $html;
}

/**
 * Empty State Component
 * @param string $icon Emoji icon
 * @param string $title Title text
 * @param string $message Message text
 * @param string|null $action Optional action button
 */
function emptyState($icon, $title, $message, $action = null) {
    $html = '<div class="empty-state">';
    $html .= "<div class=\"empty-state-icon\">{$icon}</div>";
    $html .= "<h3>{$title}</h3>";
    $html .= "<p>{$message}</p>";
    
    if ($action) {
        $html .= $action;
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Loading Spinner Component
 */
function spinner() {
    return '<div class="loading-overlay"><div class="loading-spinner"></div></div>';
}

/**
 * Grid Layout
 * @param array $items Items to display in grid
 * @param int $columns Number of columns
 */
function grid($items, $columns = 3) {
    $html = "<div class=\"grid-{$columns}\">";
    foreach ($items as $item) {
        $html .= $item;
    }
    $html .= '</div>';
    return $html;
}

/**
 * Stat Card Component
 * @param string $label Stat label
 * @param string|int $value Stat value
 * @param string $icon Icon/emoji
 * @param string|null $change Change percentage
 * @param bool $isPositive Is change positive
 */
function statCard($label, $value, $icon, $change = null, $isPositive = true) {
    $html = '<div class="card">';
    $html .= '<div class="card-body">';
    $html .= "<div style=\"font-size: 2rem; margin-bottom: 8px;\">{$icon}</div>";
    $html .= "<div style=\"color: var(--text-muted); font-size: 0.9rem; margin-bottom: 8px;\">{$label}</div>";
    $html .= "<div style=\"font-size: 1.8rem; font-weight: 700; color: var(--primary);\">{$value}</div>";
    
    if ($change) {
        $color = $isPositive ? 'var(--success)' : 'var(--error)';
        $symbol = $isPositive ? '+' : '-';
        $html .= "<div style=\"color: {$color}; font-size: 0.9rem; margin-top: 8px;\">{$symbol}{$change}%</div>";
    }
    
    $html .= '</div></div>';
    return $html;
}

/**
 * Modal Component
 * @param string $id Modal ID
 * @param string $title Modal title
 * @param string $content Modal content
 * @param string|null $footer Footer content
 */
function modal($id, $title, $content, $footer = null) {
    $html = "<div class=\"modal-overlay\" id=\"{$id}-overlay\">";
    $html .= '<div class="modal">';
    $html .= "<div class=\"modal-header\"><h2>{$title}</h2><button class=\"modal-close\" onclick=\"Modal.close('#" . $id . "-overlay')\">&times;</button></div>";
    $html .= "<div class=\"modal-body\">{$content}</div>";
    
    if ($footer) {
        $html .= "<div class=\"modal-footer\">{$footer}</div>";
    }
    
    $html .= '</div></div>';
    return $html;
}

/**
 * Breadcrumb Component
 * @param array $breadcrumbs Breadcrumb items ['label' => 'text', 'href' => 'url']
 */
function breadcrumbs($breadcrumbs) {
    $html = '<nav style="margin-bottom: 24px;"><ol style="list-style: none; display: flex; gap: 12px; margin: 0; padding: 0;">';
    
    foreach ($breadcrumbs as $index => $item) {
        $html .= '<li>';
        if (isset($item['href']) && $index < count($breadcrumbs) - 1) {
            $html .= "<a href=\"{$item['href']}\" style=\"color: var(--primary); text-decoration: none;\">{$item['label']}</a>";
        } else {
            $html .= "<span style=\"color: var(--text);\">{$item['label']}</span>";
        }
        $html .= '</li>';
        
        if ($index < count($breadcrumbs) - 1) {
            $html .= '<li style="color: var(--text-muted);">/</li>';
        }
    }
    
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Price Display Component
 * @param float $amount Amount to display
 * @param string $currency Currency symbol
 */
function price($amount, $currency = '$') {
    return "<span class=\"text-primary\" style=\"font-weight: 700;\">{$currency}" . number_format($amount, 2) . "</span>";
}

/**
 * Star Rating Component
 * @param float $rating Rating value (0-5)
 * @param int $reviews Number of reviews
 */
function starRating($rating, $reviews = 0) {
    $stars = '';
    for ($i = 0; $i < 5; $i++) {
        if ($i < floor($rating)) {
            $stars .= '★';
        } elseif ($i < $rating) {
            $stars .= '⭐';
        } else {
            $stars .= '☆';
        }
    }
    
    $html = "<div style=\"display: flex; align-items: center; gap: 8px;\">";
    $html .= "<span style=\"color: var(--accent); font-weight: 700;\">{$stars}</span>";
    $html .= "<span style=\"color: var(--text-muted); font-size: 0.9rem;\">{$rating} ({$reviews} reviews)</span>";
    $html .= '</div>';
    
    return $html;
}
