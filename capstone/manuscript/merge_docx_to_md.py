#!/usr/bin/env python3
"""
Merge DOCX content into MD, preserving annotation blocks (<updated>, <to-be-removed>, etc.)
"""
import re
import json
from pathlib import Path

DOCX_CONTENT_PATH = "capstone/manuscript/extracted_from_docx.json"
MD_PATH = "capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md"

def load_docx_content():
    with open(DOCX_CONTENT_PATH) as f:
        return json.load(f)

def load_md():
    return Path(MD_PATH).read_text(encoding='utf-8')

def extract_existing_annotations(md_content, tag_name):
    """Extract all annotation blocks for a given TAG from current MD."""
    # Find the TAG section
    tag_pattern = rf'<!--\s*TAG:\s*{re.escape(tag_name)}\s*-->'
    tag_match = re.search(tag_pattern, md_content)
    if not tag_match:
        return ''
    
    # Find the next TAG or end of file
    start_pos = tag_match.end()
    next_tag_match = re.search(r'<!--\s*TAG:\s*\S+\s*-->', md_content[start_pos:])
    end_pos = start_pos + next_tag_match.start() if next_tag_match else len(md_content)
    
    section_content = md_content[start_pos:end_pos]
    
    # Extract all annotation blocks - handle split format: <!-- <updated ...> -->content<!-- </updated> -->
    # Pattern handles both single-line and multi-line
    annotations = []
    # Pattern for <updated section="X" para="Y">content</updated>
    updated_matches = re.finditer(
        r'<!--\s*<updated\s+section="[^"]*"\s+para="\d+"\s*>\s*-->(.*?)<!--\s*</updated>\s*-->',
        section_content, re.DOTALL
    )
    for m in updated_matches:
        annotations.append(f'<!-- <updated section="{re.escape(m.group(0)[:50])}" para="X"> -->{m.group(1)}<!-- </updated> -->')
    
    # Simpler approach: find all annotation blocks with full pattern
    annotation_patterns = [
        r'<!--\s*<updated\s+[^>]*>.*?<!--\s*</updated>\s*-->',
        r'<!--\s*<to-be-removed\s+[^>]*>.*?<!--\s*</to-be-removed>\s*-->',
        r'<!--\s*<human-task\s+[^>]*>.*?<!--\s*</human-task>\s*-->',
        r'<!--\s*<fact-check\s+[^>]*>.*?<!--\s*</fact-check>\s*-->',
    ]
    
    for pattern in annotation_patterns:
        matches = re.findall(pattern, section_content, re.DOTALL)
        annotations.extend(matches)
    
    return '\n'.join(annotations) if annotations else ''

def build_tag_section(tag_name, heading, level, docx_content, md_annotations):
    """Build the complete TAG section for MD."""
    heading_prefix = '#' * level + ' '
    
    # Build the section
    parts = [
        f'<!-- TAG: {tag_name} -->',
        f'{heading_prefix}{heading}',
        '',
        '<!-- UPDATE:START -->',
        docx_content.strip(),
        '<!-- UPDATE:END -->',
    ]
    
    if md_annotations:
        parts.append('')
        parts.append(md_annotations)
    
    return '\n'.join(parts)

def main():
    # Load DOCX content
    with open("capstone/manuscript/extracted_from_docx.json") as f:
        docx_sections = json.load(f)
    
    # Load current MD
    md_content = Path("capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md").read_text(encoding='utf-8')
    
    # Extract current META
    meta_match = re.search(r'<!--\s*META:.*?-->', md_content)
    meta_line = meta_match.group(0) if meta_match else '<!-- META: docx-sync-version="2026-06-13T04:30:00.000000+00:00" docx-sha256="1b495e748c344663c7f1ae81b92653cc3679922e8bb4a8e9439c0288a4469066" -->'
    
    # Title block (before first TAG)
    first_tag = re.search(r'<!--\s*TAG:\s*\S+\s*-->', md_content)
    title_block = md_content[:first_tag.start()].rstrip() if first_tag else ''
    
    # Build new MD
    new_md_parts = [title_block, '', meta_line, '']
    
    # Tag order as in original MD
    tag_order = [
        'ch1_introduction', 'ch1_bg_of_the_study', 'ch1_background_para_2', 'ch1_background_para_3',
        'ch1_background_para_4', 'ch1_background_para_5', 'ch1_background_para_6',
        'ch1_conceptual_framework', 'ch1_objectives', 'ch1_scope_delimitations', 'ch1_significance',
        'ch2_methodology', 'ch2_research_design', 'ch2_software_model', 'ch2_project_plan',
        'ch2_project_assignment', 'ch2_population_locale', 'ch2_research_instruments',
        'ch2_data_analysis', 'references_list', 'appendix_a_use_case', 'appendix_b_letter_conduct'
    ]
    
    docx_data = load_docx_content()
    
    for tag in tag_order:
        if tag not in docx_data:
            print(f"⚠ TAG {tag} not in DOCX, skipping")
            continue
            
        data = docx_data[tag]
        annotations = extract_existing_annotations(md_content, tag)
        
        section = build_tag_section(
            tag,
            data['heading'],
            data['level'],
            data['content'],
            annotations
        )
        new_md_parts.append(section)
        print(f"✓ {tag}: heading='{data['heading']}', level={data['level']}, content_len={len(data['content'])}")
    
    # Write new MD
    new_md = '\n'.join(new_md_parts)
    backup_path = Path("capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md.backup")
    backup_path.write_text(Path("capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md").read_text(encoding='utf-8'), encoding='utf-8')
    print(f"Backup saved to {backup_path}")
    
    Path("capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md").write_text(new_md, encoding='utf-8')
    print(f"\nNew MD written with {len(tag_order)} sections")
    
    # Also save full extracted content for reference
    full_extracted = {k: v for k, v in docx_data.items()}
    with open("capstone/manuscript/full_extracted_docx.json", "w") as f:
        json.dump(full_extracted, f, indent=2)
    print("Full extracted content saved to capstone/manuscript/full_extracted_docx.json")

def load_docx_content():
    with open("capstone/manuscript/extracted_from_docx.json") as f:
        return json.load(f)

if __name__ == '__main__':
    main()