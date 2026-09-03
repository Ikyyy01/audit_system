import zipfile
import xml.etree.ElementTree as ET
import sys

def parse_xlsx(file_path):
    with zipfile.ZipFile(file_path, 'r') as z:
        # Load shared strings
        shared_strings = []
        if 'xl/sharedStrings.xml' in z.namelist():
            tree = ET.fromstring(z.read('xl/sharedStrings.xml'))
            for si in tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}si'):
                texts = [t.text or '' for t in si.findall('.//{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t')]
                shared_strings.append(''.join(texts))

        # Load workbook for sheet names
        wb_tree = ET.fromstring(z.read('xl/workbook.xml'))
        sheets = wb_tree.findall('.//{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheet')
        
        for idx, sheet in enumerate(sheets, 1):
            sheet_name = sheet.attrib.get('name')
            r_id = sheet.attrib.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')
            sheet_file = f'xl/worksheets/sheet{idx}.xml'
            
            print(f"\n{'='*30} SHEET: {sheet_name} ({sheet_file}) {'='*30}")
            if sheet_file in z.namelist():
                ws_tree = ET.fromstring(z.read(sheet_file))
                rows = ws_tree.findall('.//{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row')
                for row in rows:
                    r_idx = row.attrib.get('r')
                    cells = row.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c')
                    row_data = []
                    for c in cells:
                        ref = c.attrib.get('r')
                        t = c.attrib.get('t')
                        v = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
                        f = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}f')
                        val = v.text if v is not None else ''
                        formula = f.text if f is not None else ''
                        
                        if t == 's' and val.isdigit():
                            val = shared_strings[int(val)]
                        
                        cell_str = val
                        if formula:
                            cell_str += f" [FORMULA: {formula}]"
                        if cell_str:
                            row_data.append(f"{ref}: {cell_str}")
                    if row_data:
                        print(f"Row {r_idx:2s} | " + " | ".join(row_data))

if __name__ == '__main__':
    path = sys.argv[1] if len(sys.argv) > 1 else r"C:\KAP-MGN\audit_system\audit_system_backend\PT Indo American Seafoods Tbk\1000 Risk Assesment\1130 B. Cek Latar Belakang First Pass Data IAS 2024 Rev 1.xlsx"
    parse_xlsx(path)
