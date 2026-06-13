from docx import Document

doc = Document('/home/user/Projects/SecureCAT-v2/capstone/Sarmiento Manuscript Chapter 1 and 2.docx')
print("--- Sarmiento Paragraphs ---")
for p in doc.paragraphs:
    txt = p.text.strip()
    if txt.startswith("Chapter") or txt.startswith("APPENDIX") or txt.startswith("REFERENCES") or txt.startswith("Appendix") or txt.startswith("References"):
        print(f"Heading: '{txt}'")
