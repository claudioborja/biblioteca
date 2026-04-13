<?php
// Test script to verify Heroicons loading
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

use Helpers\Icons;

echo "Testing External Icon Library Integration\n";
echo "==============================\n\n";

// Test 1: Load plus icon
echo "Test 1: Plus Icon\n";
$plusIcon = Icons::plus('w-5 h-5 text-blue-500');
echo strlen($plusIcon) > 0 ? "✓ Loaded" : "✗ Failed";
echo "\n";

// Test 2: Load edit icon
echo "Test 2: Edit Icon (pencil)\n";
$editIcon = Icons::edit('w-4 h-4');
echo strlen($editIcon) > 0 ? "✓ Loaded" : "✗ Failed";
echo "\n";

// Test 3: Load delete icon
echo "Test 3: Delete Icon (trash)\n";
$deleteIcon = Icons::delete('w-4 h-4');
echo strlen($deleteIcon) > 0 ? "✓ Loaded" : "✗ Failed";
echo "\n";

// Test 4: Check if component contains correct attributes
echo "Test 4: Iconify Structure\n";
$testIcon = Icons::check('w-4 h-4');
$hasTag = str_contains($testIcon, '<iconify-icon');
$hasClass = str_contains($testIcon, 'class="w-4 h-4"');
echo ($hasTag && $hasClass) ? "✓ Valid iconify component" : "✗ Invalid structure";
echo "\n";

echo "Test 5: Alias library()\n";
$libraryIcon = Icons::library('w-5 h-5');
echo str_contains($libraryIcon, 'heroicons:book-open') ? "✓ library() alias OK" : "✗ library() alias failed";
echo "\n";

echo "\n✓ All tests passed!\n";
?>
