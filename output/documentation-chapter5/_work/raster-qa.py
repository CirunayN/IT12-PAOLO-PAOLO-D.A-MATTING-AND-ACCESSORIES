from pathlib import Path
import json
import hashlib
import pypdfium2 as pdfium
from PIL import Image, ImageDraw, ImageChops
from pypdf import PdfReader

ROOT = Path(__file__).resolve().parents[1]
def render(name, source):
    directory = ROOT / name
    directory.mkdir(exist_ok=True,parents=True)
    pdf = pdfium.PdfDocument(str(source))
    for i in range(len(pdf)):
        page = pdf[i]
        bitmap = page.render(scale=1.5)
        image = bitmap.to_pil().convert('RGB')
        image.save(directory / f'page-{i+1}.png')
        bitmap.close()
        page.close()
    pdf.close()
    return directory

ref = ROOT/'qa-reference'
final = render('qa-final-v4',ROOT/'qa-final-v4/final.pdf')
r = PdfReader(ROOT/'qa-final-v4/final.pdf')
texts = [p.extract_text() for p in r.pages]
changed = []
for i in range(1,52):
    a = Image.open(ref/f'page-{i}.png').convert('RGB')
    b = Image.open(final/f'page-{i}.png').convert('RGB')
    if a.size != b.size or ImageChops.difference(a,b).getbbox():
        changed.append(i)
start = next(i for i,t in enumerate(texts) if 'THE SOURCE CODE' in t)
appendix = next(i for i,t in enumerate(texts) if 'APPENDIX A' in t)
summary = {'final_pages':len(texts),'chapter_start_document_page':start+1,'chapter_end_document_page':appendix,'chapter_pages':appendix-start,'appendix_start_document_page':appendix+1,'earlier_pages_changed':changed}
(ROOT/'_work/qa-summary.json').write_text(json.dumps(summary,indent=2),encoding='utf-8')
print(json.dumps(summary,indent=2))
for first in range(1,len(texts)+1,9):
    indexes=list(range(first,min(first+9,len(texts)+1)))
    board=Image.new('RGB',(3*330,3*445),'#dddddd')
    for j,i in enumerate(indexes):
        img=Image.open(final/f'page-{i}.png')
        img.thumbnail((320,414))
        x,y=(j%3)*330,(j//3)*445
        board.paste(img,(x+5,y+24))
        ImageDraw.Draw(board).text((x+6,y+6),f'Document page {i}',fill='black')
    board.save(final/f'overview-{first}.png')
