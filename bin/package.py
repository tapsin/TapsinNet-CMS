#!/usr/bin/env python3
"""
TapsinNet dağıtım paketi üretir.

Kurallar:
  · .env, storage/database/*.sqlite* ve loglar PAKETE GİRMEZ
  · Git izli dosyaların dışındaki her şey de giremez (uploads/demo hariç —
    o .gitignore'da açıkça istisna)
  · Depo kökü değil, proje kökü arşivlenir (üstte TapsinNet-CMS-1.0.0/)
"""
import os, sys, zipfile, fnmatch, subprocess, datetime

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
VERSION = "1.1.3"
PREFIX = f"TapsinNet-CMS-{VERSION}"
OUT = os.path.join(ROOT, f"{PREFIX}.zip")

# Hiçbir koşulda pakete girmeyecek yollar
EXCLUDE_DIRS = {
    ".git", "node_modules", ".impeccable", ".hallmark",
    "storage/logs", "storage/cache", "storage/framework",
}
EXCLUDE_FILES = [".DS_Store", "Thumbs.db", "*.pid", "*.log", "*.zip",
                 "*.sqlite", "*.sqlite-shm", "*.sqlite-wal", "*.bak-*"]

def git_files():
    """Git tarafından izlenen dosyalar; yoksa tüm ağacı tara."""
    try:
        out = subprocess.run(["git", "ls-files"], cwd=ROOT, capture_output=True,
                             text=True, timeout=30)
        if out.returncode == 0 and out.stdout.strip():
            return [l for l in out.stdout.splitlines() if l.strip()]
    except (OSError, subprocess.SubprocessError):
        pass
    found = []
    for dirpath, dirnames, filenames in os.walk(ROOT):
        rel = os.path.relpath(dirpath, ROOT)
        dirnames[:] = [d for d in dirnames
                       if os.path.join(rel, d).replace(os.sep, "/") not in EXCLUDE_DIRS]
        for f in filenames:
            found.append(os.path.join(rel, f).replace(os.sep, "/"))
    return found

def excluded(rel):
    head = rel.split("/", 1)[0]
    if rel in EXCLUDE_DIRS or head in EXCLUDE_DIRS:
        return True
    for d in EXCLUDE_DIRS:
        if rel.startswith(d + "/"):
            return True
    name = os.path.basename(rel)
    return any(fnmatch.fnmatch(name, pat) for pat in EXCLUDE_FILES)

def main():
    files = git_files()
    keep = [f for f in files if not excluded(f)]
    skipped = len(files) - len(keep)

    if os.path.exists(OUT):
        os.remove(OUT)

    added = 0
    with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for rel in sorted(keep):
            src = os.path.join(ROOT, rel)
            if not os.path.isfile(src):
                continue
            z.write(src, f"{PREFIX}/{rel}")
            added += 1

    size = os.path.getsize(OUT)
    print(f"paket      : {PREFIX}.zip")
    print(f"dosya      : {added}")
    print(f"atlanan    : {skipped}  (gizli dosyalar)")
    print(f"boyut      : {size / 1024:.0f} KB")
    print(f"oluşturma  : {datetime.date.today()}")

    # Doğrulama: gizli dosya sızdı mı?
    # NOT: .env.example kasıtlı olarak pakete girer — şablondur, anahtar
    # içermez ve kullanıcı bunu .env olarak kopyalayarak kurar.
    # Sızıntı = GERÇEK .env, veritabanı, log veya .git
    with zipfile.ZipFile(OUT) as z:
        names = z.namelist()

    def is_leak(n):
        tail = n.split("/", 1)[1] if "/" in n else n
        return (tail == ".env"
                or ".sqlite" in tail
                or tail.endswith(".log")
                or "/.git/" in n
                or tail.endswith(".pid"))

    leaks = [n for n in names if is_leak(n)]
    print(f"sizinti    : {len(leaks)}"
          + ("  TEMIZ" if not leaks else "  !!! " + ", ".join(leaks[:3])))
    print(f"env.example: {'paketle' if any('.env.example' in n for n in names) else 'YOK'}"
          "  (kullanici adresi/anahtari kendisi doldurur)")
    return 0 if not leaks else 1

if __name__ == "__main__":
    sys.exit(main())
