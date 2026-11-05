import json

with open('lh.json') as f:
    data = json.load(f)

# LCP audit details
if 'largest-contentful-paint' in data['audits']:
    a = data['audits']['largest-contentful-paint']
    print(f"=== LCP === score={a['score']} displayValue={a.get('displayValue', 'N/A')}")
    if 'details' in a and a['details']:
        items = a['details'].get('items', [])
        for it in items:
            node = it.get('node', {})
            print(f"  Element: {node.get('snippet', 'N/A')}")
            print(f"  LCP: {it.get('lcpMs', 'N/A')} ms")
            print(f"  Load time: {it.get('loadTimeMs', 'N/A')} ms")
            print(f"  Wait time: {it.get('waitTimeMs', 'N/A')} ms")
            print(f"  Paint time: {it.get('paintTimeMs', 'N/A')} ms")
            print(f"  Render time: {it.get('renderTimeMs', 'N/A')} ms")

print()

# Font display audit
if 'font-display' in data['audits']:
    a = data['audits']['font-display']
    print(f"=== Font display === score={a['score']}")
    if 'details' in a and a['details']:
        for it in a['details'].get('items', []):
            print(f"  {it.get('url', 'N/A')}")

print()

# Image aspect ratio
if 'image-aspect-ratio' in data['audits']:
    a = data['audits']['image-aspect-ratio']
    print(f"=== Image aspect ratio === score={a['score']}")
    if 'details' in a and a['details']:
        for it in a['details'].get('items', []):
            node = it.get('node', {})
            print(f"  {node.get('snippet', 'N/A')}")

print()

# Network requests - document latency
if 'network-requests' in data['audits']:
    a = data['audits']['network-requests']
    print(f"=== Network requests === score={a['score']}")
    if 'details' in a and a['details']:
        for it in a['details'].get('items', []):
            url = it.get('url', '')
            if 'index.php' in url or 'logo' in url or 'hero' in url:
                print(f"  {url}")
                print(f"    startTime={it.get('startTime', 'N/A')}, endTime={it.get('endTime', 'N/A')}")
