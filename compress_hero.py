from PIL import Image
import os

src = 'assets/img/hero-ganado.webp'
with Image.open(src) as im:
    im = im.convert('RGB')
    im.save(src, 'WEBP', quality=75, method=6)
    print('hero-ganado.webp (comprimido):', os.path.getsize(src), 'bytes')

    w, h = im.size
    new_w = 800
    new_h = int(h * new_w / w)
    im_mobile = im.resize((new_w, new_h), Image.LANCZOS)
    mobile_path = 'assets/img/hero-ganado-mobile.webp'
    im_mobile.save(mobile_path, 'WEBP', quality=75, method=6)
    print('hero-ganado-mobile.webp:', os.path.getsize(mobile_path), 'bytes', f'({new_w}x{new_h})')
