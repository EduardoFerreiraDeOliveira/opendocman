<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// Home page: the properties the user can see, as cards, with search and filters.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];
$user_obj = new User($_SESSION['uid'], $pdo);
$L = property_labels();

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');
draw_header($L['home'], $last_message);

$filters = [
    'search' => trim((string) ($_GET['q'] ?? '')),
    'status' => (string) ($_GET['status'] ?? ''),
    'type' => (string) ($_GET['type'] ?? ''),
    'city' => (string) ($_GET['city'] ?? ''),
];

$isAdmin = $user_obj->isAdmin();
$visibleIds = Property::getVisibleIds($pdo, (int) $_SESSION['uid'], (int) $user_obj->getDeptId(), $isAdmin, 1);
$properties = Property::getAllWithCounts($pdo, $visibleIds, $filters);

foreach ($properties as &$row) {
    $row['type_label'] = Property::label('type', $row['type']);
    $row['status_label'] = Property::label('status', $row['status']);
    $row['price_text'] = $row['price'] === null ? '' : number_format((float) $row['price'], 0, ',', ' ') . ' €';
    $row['last_document_text'] = $row['last_document'] ? fix_date($row['last_document']) : '';
    // Non-admins only see the count of documents they may actually view.
    if (!$isAdmin) {
        $row['doc_count'] = count(Property::getDocuments($pdo, (int) $row['id'], (int) $_SESSION['uid']));
    }
    // Colour of the status badge
    $row['badge'] = [
        'for_sale' => 'success', 'for_rent' => 'info', 'reserved' => 'warning',
        'sold' => 'secondary', 'rented' => 'secondary',
    ][$row['status']] ?? 'secondary';
}
unset($row);

$types = [];
foreach (array_keys(Property::TYPES) as $code) {
    $types[] = ['code' => $code, 'label' => Property::label('type', $code)];
}
$statuses = [];
foreach (array_keys(Property::STATUSES) as $code) {
    $statuses[] = ['code' => $code, 'label' => Property::label('status', $code)];
}

$GLOBALS['smarty']->assign('L', $L);
$GLOBALS['smarty']->assign('properties', $properties);
$GLOBALS['smarty']->assign('types', $types);
$GLOBALS['smarty']->assign('statuses', $statuses);
$GLOBALS['smarty']->assign('cities', Property::getCities($pdo));
$GLOBALS['smarty']->assign('f', $filters);
$GLOBALS['smarty']->assign('is_admin', $isAdmin);

display_smarty_template('home.tpl');

draw_footer();
