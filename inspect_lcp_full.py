import json

with open('lh.json') as f:
    data = json.load(f)

a = data['audits']['largest-contentful-paint']
print(f"LCP score: {a['score']}, value: {a['numericValue']}ms, display: {a.get('displayValue')}")

# Check network requests
print("\n=== All network requests ===")
nr = data['audits'].get('network-requests', {})
if nr and 'details' in nr and nr['details']:
    for it in nr['details'].get('items', []):
        url = it.get('url', '')
        start = it.get('startTime', 'N/A')
        end = it.get('endTime', 'N/A')
        duration = it.get('duration', 'N/A')
        print(f"  {url} | start={start} end={end} dur={duration}")

# Check LCP element
lcp_elem = data['audits'].get('largest-contentful-paint-element', {})
print(f"\n=== LCP element audit ===")
print(f"score: {lcp_elem.get('score')}")
if lcp_elem and 'details' in lcp_elem and lcp_elem['details']:
    for it in lcp_elem['details'].get('items', []):
        node = it.get('node', {})
        print(f"  snippet: {node.get('snippet', 'N/A')}")
        print(f"  boundingRect: {node.get('boundingRect', 'N/A')}")

# Check render-blocking resources
rb = data['audits'].get('render-blocking-resources', {})
print(f"\n=== Render-blocking resources ===")
print(f"score: {rb.get('score')}")
if rb and 'details' in rb and rb['details']:
    for it in rb['details'].get('items', []):
        print(f"  url: {it.get('url', 'N/A')}")
        print(f"    totalBytes: {it.get('totalBytes', 'N/A')}")
        print(f"    wastedMs: {it.get('wastedMs', 'N/A')}")
        print(f"    completionTime: {it.get('completionTime', 'N/A')}")

# Check font display
fd = data['audits'].get('font-display', {})
print(f"\n=== Font display ===")
print(f"score: {fd.get('score')}")
if fd and 'details' in fd and fd['details']:
    for it in fd['details'].get('items', []):
        print(f"  url: {it.get('url', 'N/A')}")
        print(f"    fontFamily: {it.get('fontFamily', 'N/A')}")

# Check image delivery
imd = data['audits'].get('uses-optimized-images', {})
print(f"\n=== Uses optimized images ===")
print(f"score: {imd.get('score')}")
if imd and 'details' in imd and imd['details']:
    for it in imd['details'].get('items', []):
        print(f"  url: {it.get('url', 'N/A')}")
        print(f"    totalBytes: {it.get('totalBytes', 'N/A')}")
        print(f"    wastedBytes: {it.get('wastedBytes', 'N/A')}")
        print(f"    wastedPercent: {it.get('wastedPercent', 'N/A')}")
