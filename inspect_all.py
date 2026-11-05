import json

with open('lh.json') as f:
    data = json.load(f)

audits = data['audits']

print("=== LCP Breakdown ===")
lcp_breakdown = audits.get('lcp-breakdown-insight', {})
print(f"score: {lcp_breakdown.get('score')}")
print(f"displayValue: {lcp_breakdown.get('displayValue')}")

print()
print("=== Network Dependency Tree ===")
ndt = audits.get('network-dependency-tree-insight', {})
print(f"score: {ndt.get('score')}")
if ndt.get('details') and ndt['details'].get('items'):
    for it in ndt['details']['items']:
        node = it.get('node', {})
        print(f"  url: {node.get('url', 'N/A')}")
        print(f"  startTime: {node.get('startTime', 'N/A')}")
        print(f"  endTime: {node.get('endTime', 'N/A')}")
        print(f"  totalBytes: {node.get('totalBytes', 'N/A')}")
        print(f"  completionTime: {node.get('completionTime', 'N/A')}")
        print(f"  children: {[c.get('url', 'N/A') for c in node.get('children', [])]}")
        print()

print("=== Image Delivery Insight ===")
img_delivery = audits.get('image-delivery-insight', {})
print(f"score: {img_delivery.get('score')}")
if img_delivery.get('details') and img_delivery['details'].get('items'):
    for it in img_delivery['details']['items']:
        print(f"  url: {it.get('url', 'N/A')}")
        print(f"  totalBytes: {it.get('totalBytes', 'N/A')}")
        print(f"  wastedBytes: {it.get('wastedBytes', 'N/A')}")
        print(f"  displaySize: {it.get('displaySize', 'N/A')}")
        print(f"  intrinsicSize: {it.get('intrinsicSize', 'N/A')}")
        print()

print("=== Render Blocking Insight ===")
rb = audits.get('render-blocking-insight', {})
print(f"score: {rb.get('score')}")
print(f"displayValue: {rb.get('displayValue')}")
if rb.get('details') and rb['details'].get('items'):
    for it in rb['details']['items']:
        print(f"  url: {it.get('url', 'N/A')}")
        print(f"  wastedMs: {it.get('wastedMs', 'N/A')}")
        print()

print("=== Document Latency Insight ===")
dl = audits.get('document-latency-insight', {})
print(f"score: {dl.get('score')}")
print(f"displayValue: {dl.get('displayValue')}")
if dl.get('details') and dl['details'].get('items'):
    for it in dl['details']['items']:
        print(f"  url: {it.get('url', 'N/A')}")
        print(f"  timeToFirstByte: {it.get('timeToFirstByte', 'N/A')}")
        print()

print("=== Cache Insight ===")
ci = audits.get('cache-insight', {})
print(f"score: {ci.get('score')}")
if ci.get('details') and ci['details'].get('items'):
    for it in ci['details']['items']:
        print(f"  url: {it.get('url', 'N/A')}")
        print(f"  totalBytes: {it.get('totalBytes', 'N/A')}")
        print()

print("=== Font Display Insight ===")
fd = audits.get('font-display-insight', {})
print(f"score: {fd.get('score')}")
if fd.get('details') and fd['details'].get('items'):
    for it in fd['details']['items']:
        print(f"  url: {it.get('url', 'N/A')}")
        print()

print("=== Unminified CSS ===")
uc = audits.get('unminified-css', {})
print(f"score: {uc.get('score')}")
if uc.get('details') and uc['details'].get('items'):
    for it in uc['details']['items']:
        print(f"  url: {it.get('url', 'N/A')}")
        print(f"  wastedBytes: {it.get('wastedBytes', 'N/A')}")
        print()

print("=== Unminified JS ===")
uj = audits.get('unminified-javascript', {})
print(f"score: {uj.get('score')}")
if uj.get('details') and uj['details'].get('items'):
    for it in uj['details']['items']:
        print(f"  url: {it.get('url', 'N/A')}")
        print(f"  wastedBytes: {it.get('wastedBytes', 'N/A')}")
        print()

print("=== Main Thread Work ===")
mtw = audits.get('mainthread-work-breakdown', {})
print(f"score: {mtw.get('score')}")
print(f"displayValue: {mtw.get('displayValue')}")
if mtw.get('details') and mtw['details'].get('items'):
    for it in mtw['details']['items']:
        print(f"  category: {it.get('category', 'N/A')}")
        print(f"  duration: {it.get('duration', 'N/A')}")
        print()

print("=== Image Aspect Ratio ===")
ar = audits.get('image-aspect-ratio', {})
print(f"score: {ar.get('score')}")
if ar.get('details') and ar['details'].get('items'):
    for it in ar['details']['items']:
        node = it.get('node', {})
        print(f"  snippet: {node.get('snippet', 'N/A')[:200]}")

print("=== Heading Order ===")
ho = audits.get('heading-order', {})
print(f"score: {ho.get('score')}")
if ho.get('details') and ho['details'].get('items'):
    for it in ho['details']['items']:
        node = it.get('node', {})
        print(f"  snippet: {node.get('snippet', 'N/A')[:200]}")

