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

// One property: its details and the documents the user may see, grouped by category.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];
$uid = (int) $_SESSION['uid'];
$user_obj = new User($uid, $pdo);
$L = property_labels();

$propertyId = (int) ($_GET['id'] ?? 0);
$property = $propertyId > 0 ? Property::getById($pdo, $propertyId) : null;
if ($property === null) {
    header('Location: home?last_message=' . urlencode($L['error_not_found']));
    exit;
}

$isAdmin = $user_obj->isAdmin();
$documents = Property::getDocuments($pdo, $propertyId, $uid);

// Allowed when the user is an admin, the property is visible to them, or they
// can open at least one of its documents through the normal rules.
$visibleIds = Property::getVisibleIds($pdo, $uid, (int) $user_obj->getDeptId(), $isAdmin, 1);
$allowed = $isAdmin || ($visibleIds !== null && in_array($propertyId, $visibleIds, true)) || count($documents) > 0;
if (!$allowed) {
    header('Location: error?ec=4');
    exit;
}

draw_header($property['reference'] . ' - ' . $property['address'], '');

// Group documents by category
$groups = [];
foreach ($documents as $doc) {
    $catName = ($doc['category_name'] !== null && $doc['category_name'] !== '') ? $doc['category_name'] : '-';
    if (!isset($groups[$catName])) {
        $groups[$catName] = ['name' => $catName, 'docs' => []];
    }
    $suffix = strtolower(substr($doc['realname'], (int) strrpos($doc['realname'], '.') + 1));
    $doc['view_link'] = ($doc['authority'] >= 2)
        ? 'view_file?submit=view&id=' . urlencode((string) $doc['id']) . '&mimetype=' . urlencode(File::mime_by_ext($suffix))
        : '';
    $doc['details_link'] = 'details?id=' . (int) $doc['id'] . '&state=2';
    $doc['created_text'] = fix_date($doc['created']);
    $groups[$catName]['docs'][] = $doc;
}

$property['type_label'] = Property::label('type', $property['type']);
$property['status_label'] = Property::label('status', $property['status']);
$property['price_text'] = $property['price'] === null ? '' : number_format((float) $property['price'], 2, ',', ' ') . ' €';
$property['badge'] = [
    'for_sale' => 'success', 'for_rent' => 'info', 'reserved' => 'warning',
    'sold' => 'secondary', 'rented' => 'secondary',
][$property['status']] ?? 'secondary';

$GLOBALS['smarty']->assign('L', $L);
$GLOBALS['smarty']->assign('p', $property);
$GLOBALS['smarty']->assign('groups', array_values($groups));
$GLOBALS['smarty']->assign('doc_total', count($documents));
$GLOBALS['smarty']->assign('is_admin', $isAdmin);
$GLOBALS['smarty']->assign('can_add_doc', (string) $user_obj->can_add === '1');

display_smarty_template('property.tpl');

draw_footer();
