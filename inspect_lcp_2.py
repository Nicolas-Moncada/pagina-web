import json

with open('lh.json') as f:
    data = json.load(f)

a = data['audits']['largest-contentful-paint']
print(f"LCP score: {a['score']}, value: {a['numericValue']}ms, display: {a.get('displayValue')}")

# Check network requests timing  
nr = data['audits'].get('network-requests', {})
if nr and 'details' in nr and nr['details']:
    print("\n=== Network requests ===")
    for it in nr['details'].get('items', []):
        url = it.get('url', '')
        if 'hero' in url or 'logo' in url or 'index.php' in url or 'theme.css' in url:
            start = it.get('startTime', 'N/A')
            end = it.get('endTime', 'N/A')
            duration = it.get('duration', 'N/A')
            print(f'  {url} | start={start} end={end} duration={duration}')

# Check LCP element details
lcp_elem = data['audits'].get('largest-contentful-paint-element', {})
if lcp_elem and 'details' in lcp_elem:
    print(f"\n=== LCP Element ===")
    print(f"score: {lcp_elem.get('score')}")
    items = lcp_elem['details'].get('items', [])
    for it in items[:3]:
        node = it.get('node', {})
        print(f"  snippet: {node.get('snippet', 'N/A')[:150]}")
        print(f"  rect: {node.get('boundingRect', 'N/A')}")

# Check preload usage
preload = data['audits'].get('uses-rel-preload', {})
if preload:
    print(f"\n=== Preload ===")
    print(f"score: {preload.get('score')}")
