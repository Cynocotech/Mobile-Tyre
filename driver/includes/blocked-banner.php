<?php
if (!empty($GLOBALS['driver_blocked'])) {
  $reason = $GLOBALS['driver_blocked_reason'] ?? '';
  echo '<div id="blocked-banner" class="bg-red-900/90 border-b border-red-700 text-white px-4 py-3">';
  echo '<p class="font-semibold">Your account has been blocked.</p>';
  echo '<p class="text-sm text-red-200 mt-0.5">' . htmlspecialchars($reason ?: 'No reason provided.') . '</p>';
  echo '<p class="text-xs text-red-300 mt-1">Contact the office to resolve this.</p>';
  echo '</div>';
}
?>
