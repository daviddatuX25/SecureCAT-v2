from docx import Document

doc = Document('/home/user/Projects/SecureCAT-v2/capstone/BSIT Capstone Template.docx')
print("--- Template Paragraphs ---")
for p in doc.paragraphs:
    txt = p.text.strip()
    if any(k in txt for k in ["Chapter", "APPENDIX", "REFERENCES", "Appendix", "References"]):
        print(f"Heading: '{txt}'")
