"""Stage only the author's approved PDF originals at immutable, site-local URLs."""
from concurrent.futures import ThreadPoolExecutor
from hashlib import sha256
import json
from pathlib import Path
import re
import sys
from urllib.parse import quote
from urllib.request import urlopen


def stage(entry):
    slug, digest = entry["slug"], entry["sha256"]
    if not re.fullmatch(r"[a-z0-9-]+", slug) or not re.fullmatch(r"[0-9a-f]{64}", digest):
        raise ValueError(f"Invalid PDF identity: {slug}")
    if not re.fullmatch(r"[\w.-]+", entry["repo"]) or not re.fullmatch(r"[0-9a-f]{40}", entry["revision"]):
        raise ValueError(f"Invalid GitHub source for {slug}")
    url = (f"https://raw.githubusercontent.com/{author}/{entry['repo']}/"
           f"{entry['revision']}/{quote(entry['path'])}")
    file = destination / f"{slug}-{digest[:12]}.pdf"
    checksum = sha256()
    with urlopen(url, timeout=120) as source, file.open("xb") as output:
        while chunk := source.read(1024 * 1024):
            checksum.update(chunk)
            output.write(chunk)
    with file.open("rb") as output:
        is_pdf = output.read(5) == b"%PDF-"
    if not is_pdf or file.stat().st_size != entry["size"] or checksum.hexdigest() != digest:
        raise ValueError(f"Source does not match the reviewed PDF: {slug}")
    print(f"Verified {slug} ({entry['pages']} pages)", flush=True)


if __name__ == "__main__":
    manifest = json.loads(Path("content/course-notes/litianxiao.json").read_text(encoding="utf-8"))
    author = manifest["author"]
    if not re.fullmatch(r"[a-zA-Z0-9-]+", author):
        raise ValueError("Invalid GitHub account")
    destination = Path(sys.argv[1])
    destination.mkdir(parents=True, exist_ok=True)
    withdrawn = manifest.get("withdrawn_files", [])
    active = {f"{note['slug']}-{note['sha256'][:12]}.pdf" for note in manifest["notes"]}
    for name in withdrawn:
        if not re.fullmatch(r"[a-z0-9][a-z0-9-]*-[0-9a-f]{12}\.pdf", name) or name in active:
            raise ValueError(f"Invalid withdrawn PDF name: {name}")
    (destination / "withdrawn.txt").write_text("".join(name + "\n" for name in withdrawn), encoding="utf-8")
    with ThreadPoolExecutor(max_workers=4) as pool:
        list(pool.map(stage, manifest["notes"]))
