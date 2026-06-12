#!/usr/bin/env python3
"""
Assemble Ch1+Ch2 manuscript docx from markdown drafts.
Uses the BSIT Capstone Template for formatting.
Creates native Word tables with centered bold captions.
"""

import os, re, sys
from docx import Document
from docx.shared import Pt, Inches, Emu, Twips
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml.ns import qn
from docx.oxml import OxmlElement

# ─── Config ───
TEMPLATE = "/home/user/Projects/SecureCAT-v2/capstone/BSIT Capstone Template.docx"
DRAFTS_DIR = "/home/user/Projects/SecureCAT-v2/capstone/drafts"
OUTPUT = "/home/user/Projects/SecureCAT-v2/capstone/SecureCAT_Ch1_Ch2_Manuscript.docx"

# Manuscript section order with headings
SECTIONS = [
    # Ch1: chapter headings inserted before C1-01
    ("C1-01", "Background of the Study", None, None),
    ("C1-02", None, None, None),
    ("C1-03", None, None, None),
    ("C1-04", None, None, None),
    ("C1-05", None, None, None),
    ("C1-06", None, None, None),
    ("C1-07", "Conceptual Framework of the Study", "Input-Process-Output (IPO) Diagram", "section"),
    ("C1-08", None, "Narrative", "section"),
    ("C1-09", "Objectives of the Study", "General Objective", "section"),
    ("C1-10", None, None, None),  # Research questions - part of objectives
    ("C1-11", "Scope and Limitation of the Study", "Scope", "section"),
    ("C1-12", "Importance of the Study", None, None),
    # Ch2: chapter headings inserted before C2-01
    ("C2-01", "Research Design", None, None),
    ("C2-02", "Software Development Model", None, None),
    ("C2-03", "Project Plan", None, None),
    ("C2-04", "Project Assignments", None, None),
    ("C2-05", "Population and Locale of the Study", None, None),
    ("C2-06", "Research Instruments", None, None),
    ("C2-07", "Data Analysis", None, None),
]

# Chapter breakpoints: insert chapter heading pair before these prefixes
CHAPTER_HEADINGS = {
    "C1-01": ("Chapter 1", "INTRODUCTION"),
    "C2-01": ("Chapter 2", "METHODOLOGY"),
}


# ═══════════════════════════════════════════
#  Formatting helpers
# ═══════════════════════════════════════════

def set_spacing(para, line=480, before=0, after=0):
    """Set paragraph line spacing."""
    pPr = para._element.get_or_add_pPr()
    sp = pPr.find(qn('w:spacing'))
    if sp is None:
        sp = OxmlElement('w:spacing')
        pPr.append(sp)
    sp.set(qn('w:line'), str(line))
    sp.set(qn('w:lineRule'), 'auto')
    sp.set(qn('w:before'), str(before))
    sp.set(qn('w:after'), str(after))


def set_first_indent(para, emu=285750):
    """Set first-line indent (0.312 inches ≈ 5 spaces)."""
    pPr = para._element.get_or_add_pPr()
    ind = pPr.find(qn('w:ind'))
    if ind is None:
        ind = OxmlElement('w:ind')
        pPr.append(ind)
    ind.set(qn('w:firstLine'), str(emu))


def add_run(para, text, bold=False, italic=False, font_name='Times New Roman', font_size=Pt(12)):
    """Add a formatted run to a paragraph."""
    run = para.add_run(text)
    run.font.name = font_name
    run.font.size = font_size
    run.font.bold = bold
    run.font.italic = italic
    # Set all font family attributes
    rPr = run._element.get_or_add_rPr()
    rFonts = rPr.find(qn('w:rFonts'))
    if rFonts is None:
        rFonts = OxmlElement('w:rFonts')
        rPr.insert(0, rFonts)
    rFonts.set(qn('w:ascii'), font_name)
    rFonts.set(qn('w:hAnsi'), font_name)
    rFonts.set(qn('w:cs'), font_name)
    return run


def add_run_with_formatting(para, text):
    """Add text with inline markdown formatting (bold, italic)."""
    # Split on **bold** and *italic* patterns
    # Process bold first, then italic within non-bold segments
    parts = re.split(r'(\*\*\*.*?\*\*\*|\*\*.*?\*\*|\*.*?\*)', text)
    for part in parts:
        if not part:
            continue
        if part.startswith('***') and part.endswith('***'):
            add_run(para, part[3:-3], bold=True, italic=True)
        elif part.startswith('**') and part.endswith('**'):
            add_run(para, part[2:-2], bold=True)
        elif part.startswith('*') and part.endswith('*'):
            add_run(para, part[1:-1], italic=True)
        else:
            add_run(para, part)


def add_body_para(doc, text, indent=True):
    """Add a justified, double-spaced body paragraph with first-line indent."""
    para = doc.add_paragraph()
    para.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    set_spacing(para, line=480, before=0, after=0)
    if indent:
        set_first_indent(para, 285750)
    # Clean HTML comments
    text = re.sub(r'<!--.*?-->', '', text).strip()
    add_run_with_formatting(para, text)
    return para


def add_heading_para(doc, text, level="section"):
    """Add a heading paragraph matching the BSIT Capstone Template format.
    
    Template heading styles:
    - "chapter_num": centered, bold, 12pt (e.g., "Chapter 1")
    - "chapter_title": centered, bold, 12pt ALL CAPS (e.g., "INTRODUCTION")
    - "section": bold, 12pt, no special alignment (defaults to justify)
    """
    para = doc.add_paragraph()
    set_spacing(para, line=480, before=0, after=0)
    if level == "chapter_num":
        para.alignment = WD_ALIGN_PARAGRAPH.CENTER
        add_run(para, text, bold=True)
    elif level == "chapter_title":
        para.alignment = WD_ALIGN_PARAGRAPH.CENTER
        add_run(para, text.upper(), bold=True)
    elif level == "section":
        # Section headings in template use default alignment (justify/none), bold
        add_run(para, text, bold=True)
    return para


def add_caption(doc, caption_text):
    """Add a centered bold table/figure caption matching template format."""
    cap = doc.add_paragraph()
    cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
    set_spacing(cap, line=480, before=240, after=0)
    add_run(cap, caption_text, bold=True)
    return cap


def add_native_table(doc, header_cells, body_rows, caption_text=None):
    """Create a native docx table matching the BSIT Capstone Template format.

    Template spec (three-line / APA-style table):
    - Row 0 (header): top border single sz=12 + bottom border single sz=12, no left/right
    - Rows 1 to N-2: ALL borders nil
    - Last row N-1: bottom border single sz=12 only
    - Font: Times New Roman 12pt
    - Header row: centered, bold, 1.5 line spacing (360)
    - Body rows: justified, not bold, 1.5 line spacing (360)
    - Table width: 8294 dxa, fixed layout
    - Caption AFTER table: centered, bold, double spacing (480), space-before 240
    """
    num_cols = len(header_cells)
    num_rows = 1 + len(body_rows)

    table = doc.add_table(rows=num_rows, cols=num_cols)
    table.style = 'Table Grid'  # Start with grid, then override borders
    table.alignment = WD_TABLE_ALIGNMENT.LEFT

    # Set full table width and fixed layout
    tbl = table._tbl
    tblPr = tbl.find(qn('w:tblPr'))
    if tblPr is None:
        tblPr = OxmlElement('w:tblPr')
        tbl.insert(0, tblPr)

    tblW = tblPr.find(qn('w:tblW'))
    if tblW is None:
        tblW = OxmlElement('w:tblW')
        tblPr.append(tblW)
    tblW.set(qn('w:w'), '8294')
    tblW.set(qn('w:type'), 'dxa')

    tblLayout = tblPr.find(qn('w:tblLayout'))
    if tblLayout is None:
        tblLayout = OxmlElement('w:tblLayout')
        tblPr.append(tblLayout)
    tblLayout.set(qn('w:type'), 'fixed')

    # ── Apply three-line borders to every cell ──
    def set_cell_border(cell, top=None, bottom=None, start=None, end=None):
        """Set individual cell borders. Pass dict with val/sz/space/color or None."""
        tc = cell._tc
        tcPr = tc.find(qn('w:tcPr'))
        if tcPr is None:
            tcPr = OxmlElement('w:tcPr')
            tc.insert(0, tcPr)

        tcBorders = tcPr.find(qn('w:tcBorders'))
        if tcBorders is None:
            tcBorders = OxmlElement('w:tcBorders')
            tcPr.append(tcBorders)
        else:
            # Remove existing borders
            for child in list(tcBorders):
                tcBorders.remove(child)

        border_specs = {'top': top, 'bottom': bottom, 'start': start, 'end': end}
        for edge, spec in border_specs.items():
            border_el = OxmlElement(f'w:{edge}')
            if spec is None:
                border_el.set(qn('w:val'), 'nil')
            else:
                border_el.set(qn('w:val'), spec.get('val', 'single'))
                border_el.set(qn('w:sz'), spec.get('sz', '12'))
                border_el.set(qn('w:space'), spec.get('space', '0'))
                border_el.set(qn('w:color'), spec.get('color', '000000'))
            tcBorders.append(border_el)

    # Define the three lines
    line_border = {'val': 'single', 'sz': '12', 'space': '0', 'color': '000000'}

    for ri, row in enumerate(table.rows):
        for ci, cell in enumerate(row.cells):
            if ri == 0:
                # Header row: top + bottom borders
                set_cell_border(cell, top=line_border, bottom=line_border)
            elif ri == num_rows - 1:
                # Last row: bottom border only
                set_cell_border(cell, bottom=line_border)
            else:
                # Middle rows: no borders
                set_cell_border(cell)

    # ── Header row formatting: centered, bold ──
    for ci, cell_text in enumerate(header_cells):
        cell = table.rows[0].cells[ci]
        para = cell.paragraphs[0]
        para.alignment = WD_ALIGN_PARAGRAPH.CENTER
        set_spacing(para, line=360, before=0, after=0)
        # Clear default runs
        for r in list(para.runs):
            r._element.getparent().remove(r._element)
        add_run(para, cell_text.strip(), bold=True)

    # ── Body rows formatting: justified, not bold ──
    for ri, row_data in enumerate(body_rows):
        for ci in range(num_cols):
            cell_text = row_data[ci] if ci < len(row_data) else ''
            cell = table.rows[ri + 1].cells[ci]
            para = cell.paragraphs[0]
            para.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
            set_spacing(para, line=360, before=0, after=0)
            for r in list(para.runs):
                r._element.getparent().remove(r._element)
            # Handle bold within cells (e.g., **TOTAL**)
            add_run_with_formatting(para, cell_text.strip())

    # ── Caption AFTER the table: centered, bold, double-spaced ──
    if caption_text:
        cap = doc.add_paragraph()
        cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
        set_spacing(cap, line=480, before=240, after=0)
        add_run(cap, caption_text, bold=True)

    return table


# ═══════════════════════════════════════════
#  Markdown parsing
# ═══════════════════════════════════════════

def parse_md_table(table_lines):
    """Parse markdown table lines into (header, body_rows).
    Returns None if not a valid table."""
    rows = []
    for line in table_lines:
        if '|' in line:
            cells = [c.strip() for c in line.strip().strip('|').split('|')]
            rows.append(cells)

    if len(rows) < 2:
        return None

    # Check separator row (row index 1)
    sep = rows[1]
    is_sep = all(set(c.strip()) <= {'-', ':', ' '} for c in sep)
    if not is_sep:
        return None

    header = rows[0]
    body = rows[2:]
    return header, body


def extract_body(filepath):
    """Extract manuscript body content from a draft markdown file.

    Strategy:
    1. Skip everything before first [indent] line (metadata, frontmatter)
    2. Collect all lines from [indent] until the second standalone ---
       (first --- is metadata separator, second --- is end-of-body)
    3. If no [indent], use everything between ## heading and first ---

    Returns list of content blocks:
        ('text', string)
        ('table', caption_or_None, header_list, body_rows_list)
    """
    with open(filepath) as f:
        content = f.read()

    lines = content.split('\n')

    # Find the body region
    # Strategy: 
    # 1. If file has [indent], use those lines
    # 2. If file has ## heading after first ---, use everything between that and the
    #    next --- or ## Notes/Compliance heading
    # 3. Some files use leading whitespace instead of [indent]
    body_lines = []
    in_body = False
    body_ended = False
    has_indent = any('[indent]' in l for l in lines)

    for i, line in enumerate(lines):
        stripped = line.strip()

        if body_ended:
            break

        # Skip frontmatter
        if not in_body:
            # Trigger: [indent] marker
            if '[indent]' in stripped:
                in_body = True
                after = stripped.replace('[indent]', '').strip()
                if after:
                    body_lines.append(after)
                continue
            # Trigger: first ## heading after the metadata --- separator
            # (The pattern is: frontmatter, ---, ## heading, body text)
            if stripped.startswith('## ') and i > 0:
                # Check that we've passed at least one --- separator (metadata boundary)
                prev_lines = [l.strip() for l in lines[:i]]
                if '---' in prev_lines:
                    # Skip headings that are metadata sections
                    if not re.match(r'^## (Notes|Compliance|Source|References used|Evaluation|Compliance)', stripped):
                        in_body = True
                        continue
            # Also detect body text that starts without a heading (after --- separator)
            if not has_indent and stripped and not stripped.startswith('#') and not stripped.startswith('**') and not stripped.startswith('---'):
                # Check we've passed the metadata separator
                prev_lines = [l.strip() for l in lines[:i]]
                if '---' in prev_lines:
                    in_body = True
                    body_lines.append(stripped)
                    continue
            continue

        # We're in the body now
        # End markers
        if re.match(r'^## (Notes|Compliance|Source|References used|Evaluation|Compliance)', stripped):
            body_ended = True
            continue

        # Also end at standalone --- that comes AFTER body content
        if stripped == '---' and body_lines:
            # Check if next non-empty line is a ## heading
            next_lines = [l.strip() for l in lines[i+1:] if l.strip()]
            if next_lines and (next_lines[0].startswith('##') or next_lines[0].startswith('References')):
                body_ended = True
                continue
            body_lines.append('')
            continue

        # Skip [indent] markers in subsequent paragraphs
        if '[indent]' in stripped:
            after = stripped.replace('[indent]', '').strip()
            if after:
                body_lines.append(after)
            continue

        # Skip sub-headings within body (## Scope, ## Limitations, ## General Objective, etc.)
        # but keep the text after them
        if stripped.startswith('## ') and not re.match(r'^## (Notes|Compliance|Source|References)', stripped):
            # These are section sub-headings within the body - we handle them via SECTIONS config
            # Add a marker so we know a section boundary exists
            body_lines.append(f'__HEADING__:{stripped[3:]}')
            continue

        body_lines.append(line)

    if not body_lines:
        return []

    # Now parse the body lines into blocks
    blocks = []
    current_para = []
    pending_table = []
    pending_caption = None
    in_code = False

    for line in body_lines:
        stripped = line.strip() if isinstance(line, str) else line

        # Heading marker - flush and continue
        if stripped.startswith('__HEADING__:'):
            if current_para:
                text = ' '.join(current_para)
                text = re.sub(r'<!--.*?-->', '', text).strip()
                if text:
                    blocks.append(('text', text))
                current_para = []
            # Don't add heading as block - handled by SECTIONS config
            continue

        # Code block markers
        if stripped.startswith('```'):
            in_code = not in_code
            # Flush pending paragraph at code block boundary
            if current_para:
                text = ' '.join(current_para)
                text = re.sub(r'<!--.*?-->', '', text).strip()
                if text:
                    blocks.append(('text', text))
                current_para = []
            continue
        if in_code:
            # Skip ASCII art / code blocks entirely
            continue

        # Table caption (matches both "Table N." and "**Table N.**")
        if re.match(r'^\*{0,2}Table \d+\.', stripped):
            # Flush any pending paragraph
            if current_para:
                text = ' '.join(current_para)
                text = re.sub(r'<!--.*?-->', '', text).strip()
                if text:
                    blocks.append(('text', text))
                current_para = []
            # Clean caption: strip ** markers
            clean_caption = stripped.strip('*').strip()
            pending_caption = clean_caption
            continue

        # Figure caption
        if re.match(r'^\*\*Figure \d+\.', stripped):
            # Flush paragraph
            if current_para:
                text = ' '.join(current_para)
                text = re.sub(r'<!--.*?-->', '', text).strip()
                if text:
                    blocks.append(('text', text))
                current_para = []
            # Clean figure caption: **Figure N.** -> Figure N.
            fig_text = re.sub(r'^\*\*Figure (\d+\.)\*\*', r'Figure \1', stripped)
            blocks.append(('figure', fig_text))
            continue

        # Table row
        if stripped.startswith('|'):
            pending_table.append(stripped)
            continue

        # Non-table line - flush pending table
        if pending_table:
            parsed = parse_md_table(pending_table)
            if parsed:
                blocks.append(('table', pending_caption, parsed[0], parsed[1]))
            pending_table = []
            pending_caption = None

        # Empty line = paragraph break
        if stripped == '':
            if current_para:
                text = ' '.join(current_para)
                text = re.sub(r'<!--.*?-->', '', text).strip()
                if text:
                    blocks.append(('text', text))
                current_para = []
            continue

        # Numbered list items (1. **Identify** ...) - make them standalone paragraphs
        # with no first-line indent
        if re.match(r'^\d+\.\s+\*\*', stripped):
            if current_para:
                text = ' '.join(current_para)
                text = re.sub(r'<!--.*?-->', '', text).strip()
                if text:
                    blocks.append(('text', text))
                current_para = []
            cleaned = re.sub(r'<!--.*?-->', '', stripped).strip()
            blocks.append(('list_item', cleaned))
            continue

        # Regular text - but skip ASCII box-drawing art
        cleaned = re.sub(r'<!--.*?-->', '', stripped).strip()
        # Skip lines that contain box-drawing characters (ASCII diagrams)
        if cleaned and re.search(r'[┌┐└┘│─├┤┬┴┼▼▲◄►◀▶┏┓┗┛┃━┣┫┳┻╋→←↑↓]', cleaned):
            continue
        # Skip standalone diagram labels (e.g., "AI-Driven Development Lifecycle (AIDLC) software model")
        # These precede ASCII diagrams - skip them since diagrams are represented as figure placeholders
        if cleaned and cleaned.endswith(('model', 'Model', 'diagram', 'Diagram', 'chart', 'Chart')):
            if 'Lifecycle' in cleaned or 'Gantt' in cleaned or 'IPO' in cleaned:
                continue
        if cleaned:
            current_para.append(cleaned)

    # Flush remaining
    if pending_table:
        parsed = parse_md_table(pending_table)
        if parsed:
            blocks.append(('table', pending_caption, parsed[0], parsed[1]))

    if current_para:
        text = ' '.join(current_para)
        text = re.sub(r'<!--.*?-->', '', text).strip()
        if text:
            blocks.append(('text', text))

    return blocks


# ═══════════════════════════════════════════
#  Main assembly
# ═══════════════════════════════════════════

def assemble():
    print(f"Loading template: {TEMPLATE}")
    doc = Document(TEMPLATE)

    # Remove all existing content paragraphs and tables from template
    body = doc.element.body
    # Remove everything except sectPr (page layout)
    to_remove = []
    for child in list(body):
        if child.tag != qn('w:sectPr'):
            to_remove.append(child)
    for child in to_remove:
        body.remove(child)

    # Re-add page section properties if they got removed
    # (they're usually in the last sectPr element)

    # Track inserted headings to avoid duplicates
    inserted_headings = set()

    for idx, (prefix, section_heading, sub_heading, heading_level) in enumerate(SECTIONS):
        # Find matching draft file
        files = sorted([f for f in os.listdir(DRAFTS_DIR) if f.startswith(prefix) and f.endswith('.md')])
        if not files:
            print(f"  WARNING: No draft found for {prefix}")
            continue

        filepath = os.path.join(DRAFTS_DIR, files[0])
        print(f"Processing {files[0]}...")

        blocks = extract_body(filepath)
        if not blocks:
            print(f"  WARNING: No body content extracted from {files[0]}")
            continue

        # Insert chapter headings if this is a chapter start
        if prefix in CHAPTER_HEADINGS:
            ch_num, ch_title = CHAPTER_HEADINGS[prefix]
            add_heading_para(doc, ch_num, level="chapter_num")
            add_heading_para(doc, ch_title, level="chapter_title")

        # Insert section heading if specified
        if section_heading and section_heading not in inserted_headings:
            add_heading_para(doc, section_heading, level="section")
            inserted_headings.add(section_heading)

        # Insert sub-heading if specified
        if sub_heading and heading_level:
            add_heading_para(doc, sub_heading, level=heading_level)

        # Insert content blocks
        for block in blocks:
            if block[0] == 'text':
                add_body_para(doc, block[1], indent=True)
            elif block[0] == 'list_item':
                # Numbered item - no first-line indent, with bold number prefix
                para = doc.add_paragraph()
                para.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
                set_spacing(para, line=480, before=0, after=0)
                add_run_with_formatting(para, block[1])
            elif block[0] == 'table':
                _, caption, header, rows = block
                add_native_table(doc, header, rows, caption_text=caption)
            elif block[0] == 'figure':
                # Figure placeholder - add caption centered
                cap = doc.add_paragraph()
                cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
                set_spacing(cap, line=480, before=240, after=0)
                add_run(cap, block[1], bold=True)

    # Save
    print(f"\nSaving to {OUTPUT}")
    doc.save(OUTPUT)
    print("Done!")

    # Verify
    verify = Document(OUTPUT)
    print(f"\nVerification:")
    print(f"  Paragraphs: {len(verify.paragraphs)}")
    print(f"  Tables: {len(verify.tables)}")

    # Check for trimmed first letters
    trimmed = 0
    for p in verify.paragraphs:
        text = p.text.strip()
        if text and len(text) > 20 and text[0].islower() and text[0].isalpha():
            # Common trimmed patterns
            if any(text.startswith(s) for s in ['he ', 'his ', 'hat ', 'o ']):
                print(f"  TRIMMED: '{text[:80]}'")
                trimmed += 1
    print(f"  Trimmed first letters: {trimmed}")

    # Show tables
    for i, table in enumerate(verify.tables):
        first_cell = table.rows[0].cells[0].text.strip()[:40]
        num_rows = len(table.rows)
        print(f"  Table {i+1}: {num_rows} rows, first cell: '{first_cell}'")


if __name__ == '__main__':
    assemble()
