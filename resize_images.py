from PIL import Image
import os

# Resize logo.webp to match actual display size
img = Image.open('assets/img/logo.webp')
print('Logo original:', img.size, 'mode=', img.mode)
new_w = 300
new_h = int(new_w * img.size[1] / img.size[0])
img_resized = img.resize((new_w, new_h), Image.LANCZOS)
img_resized.save('assets/img/logo.webp', 'WEBP', quality=85, method=6)
print('Logo resized:', img_resized.size)
print('Logo new size:', os.path.getsize('assets/img/logo.webp'), 'bytes')

# Resize finca-andina.webp
img2 = Image.open('assets/img/finca-andina.webp')
print('Finca original:', img2.size)
new_w2 = 800
new_h2 = int(new_w2 * img2.size[1] / img2.size[0])
img2_resized = img2.resize((new_w2, new_h2), Image.LANCZOS)
img2_resized.save('assets/img/finca-andina.webp', 'WEBP', quality=85, method=6)
print('Finca resized:', img2_resized.size)
print('Finca new size:', os.path.getsize('assets/img/finca-andina.webp'), 'bytes')
