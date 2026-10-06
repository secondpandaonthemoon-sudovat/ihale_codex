from pathlib import Path
import re, sys
root=Path(__file__).resolve().parents[1]
css='\n'.join((root/'assets/css'/f).read_text(encoding='utf-8') for f in ['app.css','components.css'] if (root/'assets/css'/f).exists())
js='\n'.join(p.read_text(encoding='utf-8') for p in (root/'assets/js').glob('*.js'))
checks={
 'portrait canvas 794x1123': '#pdfDemo.pdf-demo.portrait' in css and 'width:794px!important' in css and 'height:1123px!important' in css,
 'landscape canvas 1123x794': '#pdfDemo.pdf-demo.landscape' in css and 'width:1123px!important' in css and 'height:794px!important' in css,
 'pagePx parity': "{w:794,h:1123}" in js and "{w:1123,h:794}" in js,
 'print CSS injection': '<style>${css||\'\'}</style>' in js,
 'shared saved-doc print engine': "printHtmlInFrame(d.no||'ASAY Belge'" in js and 'documentPrintCss(orientation' in js,
 'table borders in print CSS': 'border:1px solid #d7dce4!important' in js,
 'print color adjust': 'print-color-adjust:exact!important' in js,
}
failed=[k for k,v in checks.items() if not v]
if failed:
    print('FAIL:', '; '.join(failed)); sys.exit(1)
print('PASS: PDF preview/print geometry and style parity checks')
