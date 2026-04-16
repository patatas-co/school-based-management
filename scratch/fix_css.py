
import os

file_path = r'c:\xampp\htdocs\sbm\school_head\dashboard.php'

with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# Replace Brand colors and vibrant accents with neutral ones
replacements = {
    'var(--brand-600)': 'var(--n-800)',
    'var(--brand-700)': 'var(--n-900)',
    '#2563eb': 'var(--n-800)',
    'rgba(37, 99, 235, 0.2)': 'rgba(0, 0, 0, 0.1)',
    'rgba(37, 99, 235, 0.3)': 'rgba(0, 0, 0, 0.15)',
    'background: #f8fafc;': 'background: #fff;',
    'color: var(--brand-700);': 'color: var(--n-700);',
    'background: var(--brand-600);': 'background: var(--n-800);',
    'color: var(--red);': 'color: var(--n-900); font-weight: 800;',
    'â”€â”€': '--'
}

new_content = content
for old, new in replacements.items():
    new_content = new_content.replace(old, new)

# Targeted styling overrides for the AI panel to ensure "plain" look
ai_overrides = """
  .ai-assistant-btn {
    background: #fff !important;
    color: var(--n-700) !important;
    border: 1px solid var(--n-300) !important;
    box-shadow: var(--shadow-xs) !important;
  }
  .ai-assistant-btn:hover {
    background: var(--n-50) !important;
    border-color: var(--n-400) !important;
  }
  .ai-panel-header {
    background: var(--n-50) !important;
    color: var(--n-900) !important;
    border-bottom: 1px solid var(--n-200) !important;
  }
  .ai-panel-close {
    background: transparent !important;
    color: var(--n-500) !important;
  }
  .ai-panel-close:hover {
    background: var(--n-200) !important;
    color: var(--n-900) !important;
  }
  .chat-msg.user {
    background: var(--n-800) !important;
  }
  .ai-suggestion-head {
    color: var(--n-600) !important;
    background: var(--n-50) !important;
  }
  .ai-priority-high {
    background: var(--n-100) !important;
    color: var(--n-900) !important;
  }
"""

# Find where to inject overrides (at the end of the style block or in a new style tag)
# I'll just append it to the end of the internal style block if possible, or just append it before </style>
if '</style>' in new_content:
    parts = new_content.split('</style>', 1)
    new_content = parts[0] + ai_overrides + '</style>' + parts[1]

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("Done")
