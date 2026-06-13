from docx import Document

doc = Document('/home/user/Projects/SecureCAT-v2/capstone/Sarmiento Manuscript Chapter 1 and 2.docx')
in_refs = False
for p in doc.paragraphs:
    txt = p.text.strip()
    if txt == "REFERENCES":
        in_refs = True
        continue
    if in_refs:
        if txt.startswith("Chapter") or txt.startswith("APPENDIX"):
            break
        # Print first few chars and paragraph formatting
        pf = p.paragraph_format
        print(f"Ref: '{txt[:30]}...' -> Left: {pf.left_indent}, Right: {pf.right_indent}, First: {pf.first_line_indent}")
