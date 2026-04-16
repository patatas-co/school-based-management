
import os
import sys

file_path = r'c:\xampp\htdocs\sbm\school_head\dashboard.php'

# Common mojibake replacements for UTF-8 shown as Windows-1252
replacements = {
    'â€”': '—',
    'â€“': '–',
    'â€œ': '“',
    'â€': '”',
    'â†’': '→',
    'â† ': '←',
    'â€¦': '…',
    'Ã±': 'ñ',
    'Ã³': 'ó',
    'Â·': '·',
    'âœ•': '✕',
    'â–²': '▲',
    'â–¼': '▼',
    'â•': '━',
    'Ã': 'A', # fallback for common A-circumflex artifacts if needed, but risky. 
    # Let's be more specific based on the grep
    'DasmariÃ±as': 'Dasmariñas',
    'No assessment data found for SY â€”': 'No assessment data found for SY —',
    'returned for revision â€” awaiting': 'returned for revision — awaiting',
    'Searchâ€¦': 'Search…',
    'â€” Dasmariñas': '— Dasmariñas'
}

# Special case for the box drawing sequences found in comments
comments_box = {
    '// â• â• â• â• â• â• â• â• â• â• â• ': '// ═══════════',
    'â• â• â• â• â• â• â• â• â• â• â• ': '═══════════'
}

with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

new_content = content

# Priority 1: Multi-byte specific substitutions
for old, new in replacements.items():
    new_content = new_content.replace(old, new)

# Priority 2: Comments and box drawing
for old, new in comments_box.items():
    new_content = new_content.replace(old, new)

# Final cleanup of any lingering single "alien" characters that are definitely artifacts
new_content = new_content.replace('â†', '→') # Another variant

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print(f"Refined encoding for {file_path}")
