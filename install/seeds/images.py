#!/usr/bin/env python3
"""
install/seeds/images.py — สร้างไฟล์ตัวอย่างใน install/seeds/<type>/datas/

    python3 install/seeds/images.py company [school shop …]
    python3 install/seeds/images.py --all

อ่านรายการไฟล์จาก install/seeds/<type>/images.json (เขียนโดย build.php) แล้ววาด
ทุกไฟล์ขึ้นเองทั้งหมด — ไล่สี รูปทรง ภาพประกอบแบบแบน และข้อความ ไม่มีภาพถ่าย
บุคคล/สถานที่จริง หรือโลโก้ของใครทั้งสิ้น ไฟล์เอกสาร (PDF/DOCX/ZIP) ก็สร้างขึ้นเอง

ปกติไม่ต้องเรียกเอง: `php install/seeds/build.php <type>` เรียกไฟล์นี้ให้แล้ว
อ่านขนาดไฟล์จริงกลับไปใส่ seed.sql

ต้องมี Pillow (มี WebP + libraqm สำหรับจัดสระ/วรรณยุกต์ภาษาไทย) และฟอนต์
Noto Sans Thai + Noto Sans (หรือฟอนต์ tlwg เช่น Loma เป็นตัวสำรอง)

ผลลัพธ์คงที่ (seed ของสุ่มมาจาก images.json) — สร้างซ้ำได้ไฟล์เดิม
"""
import io
import json
import math
import os
import random
import sys
import time
import zipfile

from PIL import Image, ImageDraw, ImageFilter, ImageFont

HERE = os.path.dirname(os.path.abspath(__file__))

# ---------------------------------------------------------------------------
# ฟอนต์ — ไทยกับละตินคนละไฟล์ (Noto Sans Thai ไม่มีตัวละติน) วาดทีละช่วงอักษร
# ---------------------------------------------------------------------------
FONT_DIRS = ['/usr/share/fonts/truetype/noto', '/usr/share/fonts/opentype/noto', '/usr/share/fonts/truetype/tlwg']
FONT_FILES = {
    ('thai', True): ['NotoSansThai-Bold.ttf', 'Loma-Bold.ttf', 'Garuda-Bold.ttf'],
    ('thai', False): ['NotoSansThai-Regular.ttf', 'Loma.ttf', 'Garuda.ttf'],
    ('latin', True): ['NotoSans-Bold.ttf', 'Loma-Bold.ttf', 'Garuda-Bold.ttf'],
    ('latin', False): ['NotoSans-Regular.ttf', 'Loma.ttf', 'Garuda.ttf'],
}
_font_cache = {}


def _font_path(script, bold):
    for name in FONT_FILES[(script, bold)]:
        for folder in FONT_DIRS:
            path = os.path.join(folder, name)
            if os.path.isfile(path):
                return path
    return None


def font(script, size, bold=True):
    key = (script, int(size), bold)
    if key not in _font_cache:
        path = _font_path(script, bold)
        _font_cache[key] = ImageFont.truetype(path, int(size)) if path else ImageFont.load_default()
    return _font_cache[key]


def is_thai(ch):
    return '฀' <= ch <= '๿'


def runs(text):
    """แบ่งข้อความเป็นช่วงไทย/ละติน — ช่องว่างติดไปกับช่วงก่อนหน้า
    (Noto Sans Thai ไม่มีตัวเลข วงเล็บ และเครื่องหมายวรรคตอนแบบ ASCII จึงต้องเป็นช่วงละติน)"""
    out = []
    for ch in text:
        script = None if ch == ' ' else ('thai' if is_thai(ch) else 'latin')
        if not out:
            out.append([script or 'latin', ch])
        elif script is None or script == out[-1][0]:
            out[-1][1] += ch
        else:
            out.append([script, ch])
    return out


def text_width(text, size, bold=True):
    return sum(font(s, size, bold).getlength(t) for s, t in runs(text))


def draw_text(draw, xy, text, size, fill, bold=True, shadow=None):
    x, y = xy
    for script, part in runs(text):
        f = font(script, size, bold)
        # ฟอนต์ละตินกับไทยมี ascent ต่างกัน — จัดให้ baseline ตรงกัน
        dy = 0
        if script == 'latin':
            dy = font('thai', size, bold).getmetrics()[0] - f.getmetrics()[0]
        if shadow:
            draw.text((x + 2, y + dy + 2), part, font=f, fill=shadow)
        draw.text((x, y + dy), part, font=f, fill=fill)
        x += f.getlength(part)
    return x


# ตัดบรรทัดภาษาไทย: ใช้ ICU (PyICU) ตัดตามคำถ้ามี ไม่มีก็ตัดที่ช่องว่าง แล้วค่อย
# ตัดระหว่างกลุ่มอักษรเมื่อคำยาวเกินบรรทัด โดยไม่แยกสระหน้า (เ แ โ ใ ไ) ออกจาก
# พยัญชนะ และไม่ขึ้นบรรทัดด้วยสระหลัง/ไม้ยมก
try:
    import icu as _icu
except ImportError:  # pragma: no cover
    _icu = None

_COMBINING = set('\u0e31\u0e34\u0e35\u0e36\u0e37\u0e38\u0e39\u0e3a\u0e47\u0e48\u0e49\u0e4a\u0e4b\u0e4c\u0e4d\u0e4e')
_LEADING = set('\u0e40\u0e41\u0e42\u0e43\u0e44')
_FOLLOWING = set('\u0e30\u0e32\u0e33\u0e45\u0e46\u0e2f')


def clusters(word):
    out = []
    for ch in word:
        if out and (ch in _COMBINING or ch in _FOLLOWING or out[-1][-1] in _LEADING):
            out[-1] += ch
        else:
            out.append(ch)
    return out


def segments(text):
    """ช่วงที่ตัดบรรทัดได้ (แต่ละช่วงรวมช่องว่างท้ายไว้ด้วย)"""
    if _icu is not None:
        bi = _icu.BreakIterator.createLineInstance(_icu.Locale('th_TH'))
        bi.setText(text)
        out = []
        start = bi.first()
        for end in bi:
            out.append(text[start:end])
            start = end
        return out
    parts = text.split(' ')
    return [p + ' ' for p in parts[:-1]] + [parts[-1]]


def wrap(text, size, max_width, bold=True, max_lines=3):
    lines = []
    line = ''
    for seg in segments(text):
        trial = line + seg
        if text_width(trial.rstrip(), size, bold) <= max_width:
            line = trial
            continue
        if line.strip():
            lines.append(line.rstrip())
            line = ''
        if text_width(seg.rstrip(), size, bold) <= max_width:
            line = seg
            continue
        for cl in clusters(seg):
            if text_width((line + cl).rstrip(), size, bold) > max_width and line:
                lines.append(line.rstrip())
                line = cl
            else:
                line += cl
    if line.strip():
        lines.append(line.rstrip())
    if len(lines) > max_lines:
        lines = lines[:max_lines]
        last = lines[-1]
        while last and text_width(last + '…', size, bold) > max_width:
            last = last[:-1]
        lines[-1] = last.rstrip() + '…'
    return lines


def fit_lines(text, max_width, start, minimum, bold=True, max_lines=2):
    size = start
    while size > minimum:
        lines = wrap(text, size, max_width, bold, max_lines=99)
        if len(lines) <= max_lines:
            return size, lines
        size -= 2
    return minimum, wrap(text, minimum, max_width, bold, max_lines)


# ---------------------------------------------------------------------------
# สี
# ---------------------------------------------------------------------------
def rgb(value):
    value = value.lstrip('#')
    return tuple(int(value[i:i + 2], 16) for i in (0, 2, 4))


def mix(a, b, t):
    return tuple(int(round(a[i] + (b[i] - a[i]) * t)) for i in range(3))


def darker(c, t=0.35):
    return mix(c, (0, 0, 0), t)


def lighter(c, t=0.35):
    return mix(c, (255, 255, 255), t)


def gradient(size, c1, c2, vertical=False):
    """ไล่สีสองสีแบบทแยง (หรือแนวตั้ง) — ขยายจากภาพ 2x2 จึงเร็ว"""
    w, h = size
    if vertical:
        small = Image.new('RGB', (1, 2))
        small.putpixel((0, 0), c1)
        small.putpixel((0, 1), c2)
    else:
        small = Image.new('RGB', (2, 2))
        small.putpixel((0, 0), c1)
        small.putpixel((1, 0), mix(c1, c2, 0.5))
        small.putpixel((0, 1), mix(c1, c2, 0.5))
        small.putpixel((1, 1), c2)
    return small.resize((w, h), Image.BILINEAR)


def overlay(img, draw_fn):
    """วาดรูปโปร่งแสงทับภาพเดิม"""
    layer = Image.new('RGBA', img.size, (0, 0, 0, 0))
    draw_fn(ImageDraw.Draw(layer))
    return Image.alpha_composite(img.convert('RGBA'), layer).convert('RGB')


def bubbles(img, rng, colour=(255, 255, 255), count=6, alpha=(14, 34)):
    w, h = img.size

    def fn(d):
        for _ in range(count):
            r = rng.randint(int(min(w, h) * 0.15), int(min(w, h) * 0.55))
            x = rng.randint(-r // 2, w - r // 2)
            y = rng.randint(-r // 2, h - r // 2)
            d.ellipse([x, y, x + r, y + r], fill=colour + (rng.randint(*alpha),))
    return overlay(img, fn)


def chip(img, text, xy, size, bg, fg):
    draw = ImageDraw.Draw(img)
    pad = int(size * 0.55)
    tw = text_width(text, size)
    x, y = xy
    box = [x, y, x + tw + pad * 2, y + int(size * 1.9)]
    draw.rounded_rectangle(box, radius=int(size * 0.95), fill=bg)
    draw_text(draw, (x + pad, y + int(size * 0.28)), text, size, fg)
    return box


def caption_band(img, text, size, max_lines=2, band=True):
    """ข้อความสีขาวบนแถบไล่สีเข้มด้านล่างของภาพ"""
    w, h = img.size
    size, lines = fit_lines(text, w * 0.88, size, max(14, size * 0.6), max_lines=max_lines)
    line_h = int(size * 1.45)
    total = line_h * len(lines)
    top = h - total - int(size * 1.1)
    if band:
        shade = Image.new('RGBA', (w, h - top + int(size * 1.5)), (0, 0, 0, 0))
        sd = ImageDraw.Draw(shade)
        steps = shade.size[1]
        for i in range(steps):
            sd.line([(0, i), (w, i)], fill=(0, 0, 0, int(165 * (i / steps) ** 0.8)))
        base = img.convert('RGBA')
        base.alpha_composite(shade, (0, top - int(size * 1.5)))
        img = base.convert('RGB')
    draw = ImageDraw.Draw(img)
    y = top
    for line in lines:
        draw_text(draw, (int(w * 0.06), y), line, size, (255, 255, 255), shadow=(0, 0, 0) if not band else None)
        y += line_h
    return img


# ---------------------------------------------------------------------------
# ภาพประกอบแบบแบน (ไม่มีบุคคล/สถานที่จริง)
# ---------------------------------------------------------------------------
def hills(d, w, h, rng, colours, base):
    for i, colour in enumerate(colours):
        amp = h * (0.05 + 0.03 * i)
        top = base + i * h * 0.08
        phase = rng.random() * 6
        freq = 1.2 + rng.random() * 1.5
        points = [(0, h)]
        for x in range(0, w + 20, 20):
            points.append((x, top + math.sin(x / w * freq * math.pi + phase) * amp))
        points.append((w, h))
        d.polygon(points, fill=colour)


def tree(d, x, y, s, leaf, trunk):
    d.rectangle([x - s * 0.08, y - s * 0.3, x + s * 0.08, y], fill=trunk)
    d.ellipse([x - s * 0.35, y - s * 0.95, x + s * 0.35, y - s * 0.25], fill=leaf)


def person_silhouette(d, x, y, s, colour):
    """คนแบบสัญลักษณ์ (วงกลม + ไหล่) มองจากด้านหลัง"""
    d.ellipse([x - s * 0.22, y - s * 1.0, x + s * 0.22, y - s * 0.56], fill=colour)
    d.rounded_rectangle([x - s * 0.42, y - s * 0.52, x + s * 0.42, y + s * 0.3], radius=int(s * 0.3), fill=colour)


def scene(size, palette, rng, kind):
    w, h = size
    p = [rgb(c) for c in palette]
    if kind in ('city', 'office'):
        img = gradient(size, lighter(p[0], 0.55), lighter(p[1 % len(p)], 0.2), vertical=True)
        d = ImageDraw.Draw(img)
        x = -10
        while x < w:
            bw = rng.randint(int(w * 0.07), int(w * 0.15))
            bh = rng.randint(int(h * 0.3), int(h * 0.8))
            colour = mix(darker(p[rng.randrange(len(p))], 0.25), (60, 70, 90), 0.4)
            d.rectangle([x, h - bh, x + bw, h], fill=colour)
            for wy in range(h - bh + 12, h - 12, 22):
                for wx in range(x + 8, x + bw - 10, 18):
                    if rng.random() < 0.7:
                        d.rectangle([wx, wy, wx + 8, wy + 11], fill=(255, 236, 170) if rng.random() < 0.4 else lighter(colour, 0.3))
            x += bw + rng.randint(2, 10)
        d.rectangle([0, h - int(h * 0.06), w, h], fill=(70, 78, 92))
    elif kind in ('stage', 'ceremony'):
        img = gradient(size, darker(p[0], 0.45), darker(p[1 % len(p)], 0.2), vertical=True)
        d = ImageDraw.Draw(img)
        d.rectangle([0, int(h * 0.62), w, h], fill=darker(p[0], 0.6))
        d.polygon([(0, 0), (w * 0.14, 0), (w * 0.08, h * 0.62), (0, h * 0.62)], fill=(150, 30, 45))
        d.polygon([(w, 0), (w * 0.86, 0), (w * 0.92, h * 0.62), (w, h * 0.62)], fill=(150, 30, 45))
        for i in range(12):
            fx = w * 0.12 + i * w * 0.066
            d.polygon([(fx, h * 0.05), (fx + w * 0.05, h * 0.05), (fx + w * 0.025, h * 0.14)], fill=p[i % len(p)])
        d.line([(w * 0.1, h * 0.05), (w * 0.9, h * 0.05)], fill=(240, 240, 240), width=2)
        d.rounded_rectangle([w * 0.3, h * 0.22, w * 0.7, h * 0.42], radius=12, fill=lighter(p[0], 0.75))
        for i in range(3):
            person_silhouette(d, w * (0.38 + i * 0.12), h * 0.62, h * 0.16, darker(p[(i + 1) % len(p)], 0.2))
        for row in range(3):
            for i in range(14):
                person_silhouette(d, w * (0.03 + i * 0.075) + (row % 2) * 18, h * (0.84 + row * 0.08), h * 0.12, mix((35, 40, 55), p[(i + row) % len(p)], 0.25))
    elif kind in ('field', 'sport'):
        img = gradient(size, lighter(p[0], 0.6), (190, 225, 250), vertical=True)
        d = ImageDraw.Draw(img)
        d.rectangle([0, int(h * 0.45), w, h], fill=(72, 160, 90))
        for i in range(6):
            d.rectangle([0, int(h * 0.45) + i * int(h * 0.1), w, int(h * 0.45) + i * int(h * 0.1) + int(h * 0.05)], fill=(80, 170, 98))
        d.line([(w * 0.5, h * 0.45), (w * 0.5, h)], fill=(240, 240, 240), width=4)
        d.ellipse([w * 0.4, h * 0.62, w * 0.6, h * 0.86], outline=(240, 240, 240), width=4)
        d.rectangle([w * 0.04, h * 0.3, w * 0.12, h * 0.52], outline=(250, 250, 250), width=5)
        d.ellipse([w * 0.62, h * 0.7, w * 0.66, h * 0.76], fill=(250, 250, 250))
        for i in range(5):
            person_silhouette(d, w * (0.2 + i * 0.15), h * 0.9, h * 0.2, p[i % len(p)])
    elif kind in ('classroom', 'workshop', 'meeting'):
        img = gradient(size, (238, 232, 222), (220, 214, 204), vertical=True)
        d = ImageDraw.Draw(img)
        d.rectangle([0, int(h * 0.7), w, h], fill=(176, 140, 106))
        d.rounded_rectangle([w * 0.18, h * 0.1, w * 0.82, h * 0.48], radius=8, fill=(250, 250, 250), outline=(120, 120, 120), width=5)
        for i in range(4):
            d.line([(w * 0.24, h * (0.17 + i * 0.07)), (w * (0.45 + rng.random() * 0.3), h * (0.17 + i * 0.07))], fill=p[i % len(p)], width=6)
        d.rectangle([w * 0.62, h * 0.3, w * 0.76, h * 0.44], fill=lighter(p[1 % len(p)], 0.3))
        for row in range(2):
            for i in range(4):
                x = w * (0.08 + i * 0.24) + row * 30
                y = h * (0.66 + row * 0.18)
                d.rectangle([x, y, x + w * 0.16, y + h * 0.05], fill=(120, 86, 60))
                if kind != 'classroom':
                    d.polygon([(x + 20, y), (x + 60, y), (x + 55, y - 26), (x + 25, y - 26)], fill=(60, 64, 76))
                person_silhouette(d, x + w * 0.08, y + h * 0.16, h * 0.15, mix((40, 45, 60), p[(i + row) % len(p)], 0.35))
    elif kind in ('garden', 'nature', 'green'):
        img = gradient(size, (200, 232, 250), lighter(p[0], 0.7), vertical=True)
        d = ImageDraw.Draw(img)
        d.ellipse([w * 0.72, h * 0.08, w * 0.86, h * 0.29], fill=(255, 214, 102))
        hills(d, w, h, rng, [(150, 200, 120), (110, 175, 95), (86, 150, 80)], h * 0.45)
        for i in range(9):
            tree(d, w * (0.05 + i * 0.11) + rng.randint(-10, 10), h * (0.7 + rng.random() * 0.2), h * (0.25 + rng.random() * 0.15), (50 + rng.randint(0, 40), 130 + rng.randint(0, 50), 70), (110, 80, 50))
    elif kind in ('food', 'market', 'shop'):
        img = gradient(size, lighter(p[0], 0.75), lighter(p[1 % len(p)], 0.55))
        d = ImageDraw.Draw(img)
        d.rectangle([0, int(h * 0.62), w, h], fill=(196, 150, 110))
        for i in range(8):
            d.polygon([(i * w / 8, 0), ((i + 1) * w / 8, 0), ((i + 0.5) * w / 8, h * 0.12)], fill=p[i % len(p)] if i % 2 else (250, 250, 250))
        for i in range(4):
            x = w * (0.1 + i * 0.22)
            d.ellipse([x, h * 0.5, x + w * 0.16, h * 0.68], fill=(250, 250, 250))
            d.ellipse([x + 12, h * 0.48, x + w * 0.16 - 12, h * 0.6], fill=p[(i + 2) % len(p)])
    else:  # landscape
        img = gradient(size, lighter(p[0], 0.45), lighter(p[1 % len(p)], 0.75), vertical=True)
        d = ImageDraw.Draw(img)
        d.ellipse([w * 0.12, h * 0.12, w * 0.26, h * 0.33], fill=(255, 226, 140))
        hills(d, w, h, rng, [mix(p[0], (120, 170, 140), 0.6), mix(p[1 % len(p)], (70, 140, 100), 0.6), mix(p[2 % len(p)], (50, 110, 80), 0.6)], h * 0.5)
        for i in range(5):
            tree(d, w * (0.55 + i * 0.09), h * (0.78 + rng.random() * 0.08), h * 0.2, (60, 120, 80), (100, 72, 48))
    return img


# ---------------------------------------------------------------------------
# ไอคอนเส้นง่าย ๆ สำหรับหมวดหมู่ / เมนูรูปภาพ
# ---------------------------------------------------------------------------
def icon(d, kind, cx, cy, s, colour, bg):
    lw = max(3, int(s * 0.07))
    if kind in ('doc', 'download', 'file'):
        d.rounded_rectangle([cx - s * 0.32, cy - s * 0.42, cx + s * 0.32, cy + s * 0.42], radius=int(s * 0.06), outline=colour, width=lw)
        for i in range(4):
            d.line([(cx - s * 0.2, cy - s * 0.2 + i * s * 0.14), (cx + s * 0.2, cy - s * 0.2 + i * s * 0.14)], fill=colour, width=lw)
    elif kind in ('calendar', 'event'):
        d.rounded_rectangle([cx - s * 0.4, cy - s * 0.32, cx + s * 0.4, cy + s * 0.4], radius=int(s * 0.08), outline=colour, width=lw)
        d.rectangle([cx - s * 0.4, cy - s * 0.32, cx + s * 0.4, cy - s * 0.14], fill=colour)
        for r in range(2):
            for c in range(3):
                d.rectangle([cx - s * 0.26 + c * s * 0.2, cy - s * 0.02 + r * s * 0.18, cx - s * 0.16 + c * s * 0.2, cy + s * 0.08 + r * s * 0.18], fill=colour)
    elif kind in ('people', 'users', 'team'):
        for dx, sc in ((-0.2, 0.8), (0.2, 0.8), (0, 1.0)):
            r = s * 0.13 * sc
            x = cx + dx * s
            d.ellipse([x - r, cy - s * 0.3 - r, x + r, cy - s * 0.3 + r], fill=colour)
            d.rounded_rectangle([x - s * 0.2 * sc, cy - s * 0.1, x + s * 0.2 * sc, cy + s * 0.3], radius=int(s * 0.12), fill=colour)
    elif kind in ('book', 'knowledge'):
        d.polygon([(cx, cy - s * 0.25), (cx - s * 0.42, cy - s * 0.35), (cx - s * 0.42, cy + s * 0.32), (cx, cy + s * 0.42)], outline=colour, width=lw)
        d.polygon([(cx, cy - s * 0.25), (cx + s * 0.42, cy - s * 0.35), (cx + s * 0.42, cy + s * 0.32), (cx, cy + s * 0.42)], outline=colour, width=lw)
        d.line([(cx, cy - s * 0.25), (cx, cy + s * 0.42)], fill=colour, width=lw)
    elif kind in ('chat', 'forum', 'question'):
        d.rounded_rectangle([cx - s * 0.42, cy - s * 0.34, cx + s * 0.42, cy + s * 0.2], radius=int(s * 0.14), fill=colour)
        d.polygon([(cx - s * 0.2, cy + s * 0.18), (cx - s * 0.3, cy + s * 0.42), (cx, cy + s * 0.18)], fill=colour)
        for i in range(3):
            d.ellipse([cx - s * 0.2 + i * s * 0.2 - s * 0.05, cy - s * 0.1, cx - s * 0.2 + i * s * 0.2 + s * 0.05, cy], fill=bg)
    elif kind in ('chart', 'report', 'money'):
        for i, hh in enumerate((0.3, 0.5, 0.4, 0.7)):
            x = cx - s * 0.36 + i * s * 0.2
            d.rectangle([x, cy + s * 0.38 - s * hh, x + s * 0.13, cy + s * 0.38], fill=colour)
        d.line([(cx - s * 0.42, cy + s * 0.4), (cx + s * 0.42, cy + s * 0.4)], fill=colour, width=lw)
    elif kind in ('code', 'tech'):
        d.line([(cx - s * 0.15, cy - s * 0.3), (cx - s * 0.4, cy), (cx - s * 0.15, cy + s * 0.3)], fill=colour, width=lw * 2, joint='curve')
        d.line([(cx + s * 0.15, cy - s * 0.3), (cx + s * 0.4, cy), (cx + s * 0.15, cy + s * 0.3)], fill=colour, width=lw * 2, joint='curve')
        d.line([(cx + s * 0.07, cy - s * 0.36), (cx - s * 0.07, cy + s * 0.36)], fill=colour, width=lw * 2)
    elif kind in ('megaphone', 'news', 'announce'):
        d.polygon([(cx - s * 0.35, cy - s * 0.1), (cx + s * 0.25, cy - s * 0.38), (cx + s * 0.25, cy + s * 0.38), (cx - s * 0.35, cy + s * 0.1)], fill=colour)
        d.rectangle([cx - s * 0.42, cy - s * 0.12, cx - s * 0.3, cy + s * 0.12], fill=colour)
        d.line([(cx - s * 0.26, cy + s * 0.1), (cx - s * 0.18, cy + s * 0.38)], fill=colour, width=lw * 2)
    elif kind in ('star', 'award', 'trophy'):
        pts = []
        for i in range(10):
            r = s * (0.42 if i % 2 == 0 else 0.18)
            a = -math.pi / 2 + i * math.pi / 5
            pts.append((cx + r * math.cos(a), cy + r * math.sin(a)))
        d.polygon(pts, fill=colour)
    elif kind in ('cart', 'shop'):
        d.line([(cx - s * 0.45, cy - s * 0.3), (cx - s * 0.32, cy - s * 0.3), (cx - s * 0.2, cy + s * 0.18), (cx + s * 0.34, cy + s * 0.18), (cx + s * 0.42, cy - s * 0.18), (cx - s * 0.26, cy - s * 0.18)], fill=colour, width=lw, joint='curve')
        for x in (-0.12, 0.26):
            d.ellipse([cx + x * s - s * 0.07, cy + s * 0.26, cx + x * s + s * 0.07, cy + s * 0.4], fill=colour)
    elif kind in ('truck', 'delivery'):
        d.rectangle([cx - s * 0.42, cy - s * 0.25, cx + s * 0.12, cy + s * 0.18], fill=colour)
        d.polygon([(cx + s * 0.12, cy - s * 0.1), (cx + s * 0.32, cy - s * 0.1), (cx + s * 0.44, cy + s * 0.04), (cx + s * 0.44, cy + s * 0.18), (cx + s * 0.12, cy + s * 0.18)], fill=colour)
        for x in (-0.25, 0.28):
            d.ellipse([cx + x * s - s * 0.1, cy + s * 0.12, cx + x * s + s * 0.1, cy + s * 0.32], fill=colour, outline=bg, width=lw)
    elif kind in ('shield', 'guarantee'):
        d.polygon([(cx, cy - s * 0.42), (cx + s * 0.36, cy - s * 0.28), (cx + s * 0.3, cy + s * 0.15), (cx, cy + s * 0.42), (cx - s * 0.3, cy + s * 0.15), (cx - s * 0.36, cy - s * 0.28)], fill=colour)
        d.line([(cx - s * 0.14, cy), (cx - s * 0.02, cy + s * 0.12), (cx + s * 0.16, cy - s * 0.12)], fill=bg, width=lw * 2, joint='curve')
    elif kind in ('leaf', 'green'):
        d.ellipse([cx - s * 0.36, cy - s * 0.4, cx + s * 0.36, cy + s * 0.3], fill=colour)
        d.line([(cx - s * 0.3, cy + s * 0.42), (cx + s * 0.22, cy - s * 0.26)], fill=bg, width=lw)
    elif kind in ('phone', 'contact'):
        d.rounded_rectangle([cx - s * 0.22, cy - s * 0.42, cx + s * 0.22, cy + s * 0.42], radius=int(s * 0.08), outline=colour, width=lw)
        d.ellipse([cx - s * 0.04, cy + s * 0.28, cx + s * 0.04, cy + s * 0.36], fill=colour)
    elif kind in ('mail', 'email'):
        d.rectangle([cx - s * 0.42, cy - s * 0.28, cx + s * 0.42, cy + s * 0.28], outline=colour, width=lw)
        d.line([(cx - s * 0.42, cy - s * 0.28), (cx, cy + s * 0.05), (cx + s * 0.42, cy - s * 0.28)], fill=colour, width=lw)
    elif kind in ('bulb', 'idea', 'tips'):
        d.ellipse([cx - s * 0.28, cy - s * 0.42, cx + s * 0.28, cy + s * 0.12], fill=colour)
        d.rectangle([cx - s * 0.13, cy + s * 0.1, cx + s * 0.13, cy + s * 0.3], fill=colour)
        d.line([(cx - s * 0.13, cy + s * 0.36), (cx + s * 0.13, cy + s * 0.36)], fill=colour, width=lw)
    elif kind in ('camera', 'photo', 'gallery'):
        d.rounded_rectangle([cx - s * 0.42, cy - s * 0.24, cx + s * 0.42, cy + s * 0.34], radius=int(s * 0.08), fill=colour)
        d.rectangle([cx - s * 0.14, cy - s * 0.36, cx + s * 0.14, cy - s * 0.22], fill=colour)
        d.ellipse([cx - s * 0.17, cy - s * 0.12, cx + s * 0.17, cy + s * 0.22], fill=bg)
        d.ellipse([cx - s * 0.09, cy - s * 0.04, cx + s * 0.09, cy + s * 0.14], fill=colour)
    elif kind in ('play', 'video'):
        d.ellipse([cx - s * 0.42, cy - s * 0.42, cx + s * 0.42, cy + s * 0.42], fill=colour)
        d.polygon([(cx - s * 0.12, cy - s * 0.2), (cx + s * 0.22, cy), (cx - s * 0.12, cy + s * 0.2)], fill=bg)
    elif kind in ('gear', 'service', 'settings'):
        for i in range(8):
            a = i * math.pi / 4
            d.line([(cx, cy), (cx + math.cos(a) * s * 0.42, cy + math.sin(a) * s * 0.42)], fill=colour, width=int(s * 0.16))
        d.ellipse([cx - s * 0.3, cy - s * 0.3, cx + s * 0.3, cy + s * 0.3], fill=colour)
        d.ellipse([cx - s * 0.12, cy - s * 0.12, cx + s * 0.12, cy + s * 0.12], fill=bg)
    elif kind in ('school', 'building', 'home'):
        d.polygon([(cx - s * 0.46, cy - s * 0.05), (cx, cy - s * 0.42), (cx + s * 0.46, cy - s * 0.05)], fill=colour)
        d.rectangle([cx - s * 0.34, cy - s * 0.05, cx + s * 0.34, cy + s * 0.4], fill=colour)
        d.rectangle([cx - s * 0.08, cy + s * 0.12, cx + s * 0.08, cy + s * 0.4], fill=bg)
    else:  # info
        d.ellipse([cx - s * 0.42, cy - s * 0.42, cx + s * 0.42, cy + s * 0.42], outline=colour, width=lw)
        d.ellipse([cx - s * 0.05, cy - s * 0.26, cx + s * 0.05, cy - s * 0.16], fill=colour)
        d.rectangle([cx - s * 0.045, cy - s * 0.08, cx + s * 0.045, cy + s * 0.26], fill=colour)


# ---------------------------------------------------------------------------
# ชนิดของไฟล์
# ---------------------------------------------------------------------------
ART = ['landscape', 'city', 'classroom', 'garden', 'stage', 'field', 'workshop', 'food']


def varied_scene(size, palette, rng, kind):
    """ฉากเดียวกันแต่ไม่ซ้ำกัน — สลับลำดับสี ซูม/ครอปคนละมุม และกลับซ้ายขวา
    (รูปในอัลบั้มเดียวกันจึงไม่เหมือนกันทุกใบ)"""
    shift = rng.randrange(len(palette))
    pal = palette[shift:] + palette[:shift]
    w, h = size
    zoom = 1.0 + rng.random() * 0.4
    big = scene((int(w * zoom), int(h * zoom)), pal, rng, kind)
    x = rng.randint(0, big.size[0] - w)
    y = rng.randint(0, big.size[1] - h)
    img = big.crop((x, y, x + w, y + h))
    if rng.random() < 0.5:
        img = img.transpose(Image.FLIP_LEFT_RIGHT)
    return img


def make_article(item, palette, rng):
    w, h = item.get('size', [800, 450])
    art = item.get('art') or ''
    if art:
        img = varied_scene((w, h), palette, rng, art)
    else:
        p = [rgb(c) for c in palette]
        c1 = p[rng.randrange(len(p))]
        c2 = p[(p.index(c1) + 1 + rng.randrange(len(p) - 1)) % len(p)]
        img = gradient((w, h), c1, darker(c2, 0.15))
        img = bubbles(img, rng)
        d = ImageDraw.Draw(img)
        icon(d, rng.choice(['doc', 'calendar', 'people', 'book', 'chart', 'star', 'megaphone', 'bulb', 'leaf', 'gear']), w * 0.78, h * 0.34, h * 0.38, (255, 255, 255), c1)
    label = item.get('label') or ''
    if label:
        chip(img, label, (int(w * 0.05), int(h * 0.07)), max(15, h // 26), (255, 255, 255), darker(rgb(palette[0]), 0.1))
    return caption_band(img, item.get('caption', ''), max(24, h // 13))


def make_category(item, palette, rng):
    w, h = item.get('size', [480, 320])
    p = [rgb(c) for c in palette]
    c1 = p[rng.randrange(len(p))]
    img = gradient((w, h), lighter(c1, 0.15), darker(c1, 0.25))
    img = bubbles(img, rng, count=4)
    d = ImageDraw.Draw(img)
    d.ellipse([w / 2 - h * 0.25, h * 0.12, w / 2 + h * 0.25, h * 0.62], fill=(255, 255, 255))
    icon(d, item.get('label') or 'info', w / 2, h * 0.37, h * 0.32, c1, (255, 255, 255))
    text = item.get('caption', '')
    size, lines = fit_lines(text, w * 0.9, max(22, h // 10), 14, max_lines=1)
    tw = text_width(lines[0], size)
    draw_text(d, ((w - tw) / 2, h * 0.72), lines[0], size, (255, 255, 255))
    return img


def make_photo(item, palette, rng):
    w, h = item.get('size', [800, 533])
    img = varied_scene((w, h), palette, rng, item.get('scene') or 'landscape')
    img = img.filter(ImageFilter.GaussianBlur(0.6))
    label = item.get('label') or ''
    if label:
        chip(img, label, (int(w * 0.03), int(h * 0.04)), max(13, h // 32), (255, 255, 255), (50, 50, 60))
    return caption_band(img, item.get('caption', ''), max(18, h // 20), max_lines=1)


SKIN = [(241, 205, 176), (224, 172, 140), (198, 140, 105), (160, 110, 80), (250, 219, 196)]
HAIR = [(40, 32, 30), (70, 50, 40), (25, 25, 30), (110, 80, 60), (150, 150, 150)]


def make_avatar(item, palette, rng):
    w, h = item.get('size', [320, 400])
    p = [rgb(c) for c in palette]
    bg = p[rng.randrange(len(p))]
    img = gradient((w, h), lighter(bg, 0.75), lighter(bg, 0.45), vertical=True)
    img = bubbles(img, rng, count=3, alpha=(20, 40))
    d = ImageDraw.Draw(img)
    style = item.get('style') or rng.choice(['m', 'f'])
    skin = SKIN[rng.randrange(len(SKIN))]
    hair = HAIR[rng.randrange(len(HAIR))]
    outfit = {
        'khaki': (176, 150, 100), 'white': (245, 245, 245), 'navy': (40, 60, 110),
        'suit': (55, 60, 75), 'blue': (70, 120, 190), 'pink': (220, 120, 150), 'green': (70, 140, 110)
    }
    wear = item.get('wear') or ('khaki' if rng.random() < 0.35 else rng.choice(['suit', 'navy', 'blue', 'green', 'pink' if 'f' in style else 'suit']))
    cloth = outfit.get(wear, (70, 120, 190))
    cx = w / 2
    # ผมยาวด้านหลัง
    if style in ('f', 'f-long'):
        d.rounded_rectangle([cx - w * 0.25, h * 0.2, cx + w * 0.25, h * 0.62], radius=int(w * 0.18), fill=hair)
    # ลำตัว + คอ
    d.rounded_rectangle([cx - w * 0.42, h * 0.66, cx + w * 0.42, h * 1.15], radius=int(w * 0.2), fill=cloth)
    d.rectangle([cx - w * 0.07, h * 0.52, cx + w * 0.07, h * 0.7], fill=darker(skin, 0.08))
    if wear in ('suit', 'navy'):
        d.polygon([(cx - w * 0.12, h * 0.66), (cx, h * 0.84), (cx + w * 0.12, h * 0.66)], fill=(250, 250, 250))
        if 'm' in style:
            d.polygon([(cx - w * 0.025, h * 0.68), (cx + w * 0.025, h * 0.68), (cx + w * 0.035, h * 0.82), (cx, h * 0.86), (cx - w * 0.035, h * 0.82)], fill=p[0])
    elif wear == 'khaki':
        d.polygon([(cx - w * 0.12, h * 0.66), (cx, h * 0.76), (cx + w * 0.12, h * 0.66)], fill=darker(cloth, 0.15))
        d.rectangle([cx - w * 0.3, h * 0.78, cx - w * 0.18, h * 0.8], fill=(200, 170, 60))
    else:
        d.polygon([(cx - w * 0.1, h * 0.66), (cx, h * 0.74), (cx + w * 0.1, h * 0.66)], fill=darker(cloth, 0.2))
    # ศีรษะ
    d.ellipse([cx - w * 0.2, h * 0.2, cx + w * 0.2, h * 0.58], fill=skin)
    # ผม
    if style in ('m', 'm-short'):
        d.chord([cx - w * 0.21, h * 0.16, cx + w * 0.21, h * 0.44], 180, 360, fill=hair)
    elif style == 'm-bald':
        d.chord([cx - w * 0.21, h * 0.3, cx + w * 0.21, h * 0.5], 170, 200, fill=hair)
        d.chord([cx - w * 0.21, h * 0.3, cx + w * 0.21, h * 0.5], 340, 370, fill=hair)
    elif style == 'f-bun':
        d.ellipse([cx - w * 0.1, h * 0.1, cx + w * 0.1, h * 0.24], fill=hair)
        d.chord([cx - w * 0.21, h * 0.17, cx + w * 0.21, h * 0.45], 180, 360, fill=hair)
    else:
        d.chord([cx - w * 0.22, h * 0.16, cx + w * 0.22, h * 0.48], 170, 370, fill=hair)
    # หน้า (ตา ยิ้ม) แบบการ์ตูน
    eye = (50, 40, 40)
    for dx in (-0.075, 0.075):
        d.ellipse([cx + dx * w - w * 0.018, h * 0.4, cx + dx * w + w * 0.018, h * 0.425], fill=eye)
    d.arc([cx - w * 0.06, h * 0.44, cx + w * 0.06, h * 0.5], 20, 160, fill=(160, 70, 70), width=max(2, w // 110))
    if rng.random() < 0.3:
        for dx in (-0.075, 0.075):
            d.ellipse([cx + dx * w - w * 0.045, h * 0.39, cx + dx * w + w * 0.045, h * 0.435], outline=(60, 60, 70), width=max(2, w // 120))
        d.line([(cx - w * 0.03, h * 0.41), (cx + w * 0.03, h * 0.41)], fill=(60, 60, 70), width=max(2, w // 120))
    return img


def make_video(item, palette, rng):
    w, h = item.get('size', [480, 360])
    img = varied_scene((w, h), palette, rng, rng.choice(ART))
    img = overlay(img, lambda d: d.rectangle([0, 0, w, h], fill=(0, 0, 0, 70)))
    d = ImageDraw.Draw(img)
    r = h * 0.14
    d.ellipse([w / 2 - r, h * 0.42 - r, w / 2 + r, h * 0.42 + r], fill=(230, 40, 40))
    d.polygon([(w / 2 - r * 0.35, h * 0.42 - r * 0.45), (w / 2 + r * 0.5, h * 0.42), (w / 2 - r * 0.35, h * 0.42 + r * 0.45)], fill=(255, 255, 255))
    chip(img, item.get('label') or 'VIDEO', (int(w * 0.04), int(h * 0.05)), max(13, h // 26), (255, 255, 255), (200, 30, 30))
    return caption_band(img, item.get('caption', ''), max(18, h // 15))


def make_slide(item, palette, rng, compact=False):
    w, h = item.get('size', [1200, 450])
    p = [rgb(c) for c in palette]
    c1 = p[rng.randrange(len(p))]
    c2 = p[(p.index(c1) + 1) % len(p)]
    img = gradient((w, h), darker(c1, 0.2), darker(c2, 0.35))
    img = bubbles(img, rng, count=5)
    # ภาพประกอบด้านขวาในกรอบมน
    art_w = int(w * (0.36 if compact else 0.4))
    art_h = int(h * (0.72 if compact else 0.78))
    art = scene((art_w, art_h), palette, rng, item.get('scene') or rng.choice(ART))
    mask = Image.new('L', (art_w, art_h), 0)
    ImageDraw.Draw(mask).rounded_rectangle([0, 0, art_w, art_h], radius=int(h * 0.08), fill=255)
    ax = w - art_w - int(w * 0.05)
    ay = (h - art_h) // 2
    shadow = Image.new('RGBA', img.size, (0, 0, 0, 0))
    ImageDraw.Draw(shadow).rounded_rectangle([ax + 10, ay + 14, ax + art_w + 10, ay + art_h + 14], radius=int(h * 0.08), fill=(0, 0, 0, 70))
    shadow = shadow.filter(ImageFilter.GaussianBlur(12))
    base = Image.alpha_composite(img.convert('RGBA'), shadow).convert('RGB')
    base.paste(art, (ax, ay), mask)
    img = base
    d = ImageDraw.Draw(img)
    left = int(w * 0.06)
    text_w = ax - left - int(w * 0.04)
    y = int(h * (0.16 if not compact else 0.12))
    label = item.get('label') or ''
    if label:
        box = chip(img, label, (left, y), max(14, int(h * (0.042 if not compact else 0.07))), (255, 255, 255), darker(c1, 0.1))
        y = box[3] + int(h * 0.05)
    size, lines = fit_lines(item.get('caption', ''), text_w, int(h * (0.13 if not compact else 0.19)), int(h * 0.075), max_lines=2)
    for line in lines:
        draw_text(d, (left, y), line, size, (255, 255, 255), shadow=darker(c1, 0.5))
        y += int(size * 1.35)
    sub = item.get('subtitle') or ''
    if sub and sub != item.get('caption'):
        s2, sub_lines = fit_lines(sub, text_w, int(h * (0.06 if not compact else 0.1)), int(h * 0.045), bold=False, max_lines=2)
        y += int(h * 0.02)
        for line in sub_lines:
            draw_text(d, (left, y), line, s2, (235, 240, 250), bold=False)
            y += int(s2 * 1.5)
    button = item.get('button') or ''
    if button and y < h * 0.84:
        chip(img, button, (left, int(y + h * 0.03)), max(14, int(h * (0.048 if not compact else 0.075))), (255, 196, 60), (40, 40, 40))
    return img


def make_imagemenu(item, palette, rng):
    w, h = item.get('size', [400, 120])
    p = [rgb(c) for c in palette]
    c1 = p[rng.randrange(len(p))]
    img = gradient((w, h), lighter(c1, 0.88), lighter(c1, 0.7))
    d = ImageDraw.Draw(img)
    d.rounded_rectangle([1, 1, w - 2, h - 2], radius=int(h * 0.16), outline=lighter(c1, 0.35), width=2)
    r = h * 0.34
    cx, cy = h * 0.55, h / 2
    d.ellipse([cx - r, cy - r, cx + r, cy + r], fill=c1)
    icon(d, item.get('icon') or 'info', cx, cy, r * 1.25, (255, 255, 255), c1)
    left = int(h * 1.05)
    tw = w - left - int(h * 0.15)
    size, lines = fit_lines(item.get('caption', ''), tw, int(h * 0.24), int(h * 0.15), max_lines=1)
    sub = item.get('subtitle') or ''
    y = int(h * (0.22 if sub and sub != item.get('caption') else 0.34))
    draw_text(d, (left, y), lines[0], size, darker(c1, 0.45))
    if sub and sub != item.get('caption'):
        s2, sub_lines = fit_lines(sub, tw, int(h * 0.15), int(h * 0.11), bold=False, max_lines=1)
        draw_text(d, (left, y + int(size * 1.45)), sub_lines[0], s2, (90, 90, 100), bold=False)
    return img


def make_portfolio(item, palette, rng):
    w, h = item.get('size', [800, 600])
    p = [rgb(c) for c in palette]
    c1 = p[rng.randrange(len(p))]
    img = gradient((w, h), lighter(c1, 0.55), lighter(p[(p.index(c1) + 1) % len(p)], 0.3))
    img = bubbles(img, rng, count=4)
    d = ImageDraw.Draw(img)
    label = (item.get('label') or '').lower()
    if 'app' in label or 'mobile' in label or 'แอป' in label:
        # โทรศัพท์
        pw, ph = w * 0.3, h * 0.82
        x0, y0 = (w - pw) / 2, h * 0.07
        d.rounded_rectangle([x0, y0, x0 + pw, y0 + ph], radius=int(pw * 0.14), fill=(30, 32, 40))
        sx0, sy0 = x0 + pw * 0.06, y0 + ph * 0.05
        sx1, sy1 = x0 + pw * 0.94, y0 + ph * 0.95
        d.rounded_rectangle([sx0, sy0, sx1, sy1], radius=int(pw * 0.09), fill=(250, 250, 252))
        d.rectangle([sx0, sy0 + 10, sx1, sy0 + ph * 0.2], fill=c1)
        for i in range(4):
            yy = sy0 + ph * (0.25 + i * 0.16)
            d.rounded_rectangle([sx0 + 12, yy, sx1 - 12, yy + ph * 0.12], radius=10, fill=lighter(p[i % len(p)], 0.7))
            d.ellipse([sx0 + 22, yy + 10, sx0 + 22 + ph * 0.08, yy + 10 + ph * 0.08], fill=p[i % len(p)])
    else:
        # หน้าต่างเบราว์เซอร์
        x0, y0, x1, y1 = w * 0.08, h * 0.08, w * 0.92, h * 0.8
        d.rounded_rectangle([x0, y0, x1, y1], radius=14, fill=(250, 250, 252))
        d.rounded_rectangle([x0, y0, x1, y0 + 40], radius=14, fill=(226, 228, 234))
        d.rectangle([x0, y0 + 26, x1, y0 + 40], fill=(226, 228, 234))
        for i, col in enumerate([(240, 90, 80), (245, 190, 60), (90, 200, 100)]):
            d.ellipse([x0 + 16 + i * 22, y0 + 13, x0 + 30 + i * 22, y0 + 27], fill=col)
        d.rounded_rectangle([x0 + 100, y0 + 10, x1 - 20, y0 + 30], radius=10, fill=(250, 250, 252))
        d.rectangle([x0, y0 + 40, x1, y0 + 80], fill=darker(c1, 0.1))
        for i in range(4):
            d.rounded_rectangle([x1 - 90 - i * 80, y0 + 54, x1 - 30 - i * 80, y0 + 66], radius=6, fill=lighter(c1, 0.6))
        d.rectangle([x0, y0 + 80, x1, y0 + (y1 - y0) * 0.5], fill=lighter(c1, 0.3))
        d.rounded_rectangle([x0 + 40, y0 + 110, x0 + (x1 - x0) * 0.5, y0 + 130], radius=8, fill=(255, 255, 255))
        d.rounded_rectangle([x0 + 40, y0 + 145, x0 + (x1 - x0) * 0.38, y0 + 160], radius=7, fill=lighter(c1, 0.75))
        cw = (x1 - x0 - 80) / 3
        for i in range(3):
            cx0 = x0 + 20 + i * (cw + 20)
            cy0 = y0 + (y1 - y0) * 0.56
            d.rounded_rectangle([cx0, cy0, cx0 + cw, y1 - 20], radius=10, fill=lighter(p[(i + 1) % len(p)], 0.75))
            d.rectangle([cx0 + 14, cy0 + 16, cx0 + cw - 14, cy0 + 60], fill=lighter(p[(i + 1) % len(p)], 0.4))
    if label:
        chip(img, item.get('label'), (int(w * 0.05), int(h * 0.84)), max(14, h // 34), darker(c1, 0.25), (255, 255, 255))
    d = ImageDraw.Draw(img)
    size, lines = fit_lines(item.get('caption', ''), w * 0.6, max(20, h // 24), 14, max_lines=1)
    tw = text_width(lines[0], size)
    draw_text(d, (w * 0.95 - tw, h * 0.855), lines[0], size, darker(c1, 0.55))
    return img


def make_product(item, palette, rng):
    """สินค้าแบบภาพประกอบบนพื้นสตูดิโอ — รูปทรงตาม 'shape'"""
    w, h = item.get('size', [800, 800])
    p = [rgb(c) for c in palette]
    colour = rgb(item['color']) if item.get('color') else p[rng.randrange(len(p))]
    img = gradient((w, h), (248, 247, 245), (232, 230, 226), vertical=True)
    d = ImageDraw.Draw(img)
    shadow = Image.new('RGBA', img.size, (0, 0, 0, 0))
    ImageDraw.Draw(shadow).ellipse([w * 0.22, h * 0.8, w * 0.78, h * 0.88], fill=(0, 0, 0, 60))
    img = Image.alpha_composite(img.convert('RGBA'), shadow.filter(ImageFilter.GaussianBlur(14))).convert('RGB')
    d = ImageDraw.Draw(img)
    shape = item.get('shape') or 'box'
    dark = darker(colour, 0.25)
    light = lighter(colour, 0.35)
    if shape == 'shirt':
        d.polygon([(w * 0.3, h * 0.22), (w * 0.42, h * 0.18), (w * 0.5, h * 0.24), (w * 0.58, h * 0.18), (w * 0.7, h * 0.22), (w * 0.84, h * 0.36),
                   (w * 0.74, h * 0.44), (w * 0.7, h * 0.4), (w * 0.7, h * 0.82), (w * 0.3, h * 0.82), (w * 0.3, h * 0.4), (w * 0.26, h * 0.44), (w * 0.16, h * 0.36)], fill=colour)
        d.arc([w * 0.42, h * 0.14, w * 0.58, h * 0.28], 0, 180, fill=dark, width=8)
    elif shape == 'mug':
        d.rounded_rectangle([w * 0.28, h * 0.3, w * 0.64, h * 0.8], radius=30, fill=colour)
        d.ellipse([w * 0.28, h * 0.26, w * 0.64, h * 0.34], fill=light)
        d.arc([w * 0.56, h * 0.4, w * 0.78, h * 0.66], 270, 90, fill=colour, width=int(w * 0.04))
    elif shape == 'bottle':
        d.rounded_rectangle([w * 0.38, h * 0.3, w * 0.62, h * 0.82], radius=40, fill=colour)
        d.rectangle([w * 0.45, h * 0.16, w * 0.55, h * 0.32], fill=dark)
        d.rectangle([w * 0.38, h * 0.48, w * 0.62, h * 0.64], fill=(250, 250, 250))
    elif shape == 'bag':
        d.arc([w * 0.36, h * 0.16, w * 0.64, h * 0.46], 180, 360, fill=dark, width=int(w * 0.025))
        d.polygon([(w * 0.26, h * 0.32), (w * 0.74, h * 0.32), (w * 0.78, h * 0.82), (w * 0.22, h * 0.82)], fill=colour)
    elif shape == 'shoe':
        d.polygon([(w * 0.18, h * 0.66), (w * 0.2, h * 0.44), (w * 0.36, h * 0.44), (w * 0.5, h * 0.56), (w * 0.8, h * 0.62), (w * 0.84, h * 0.72), (w * 0.18, h * 0.72)], fill=colour)
        d.rectangle([w * 0.18, h * 0.72, w * 0.84, h * 0.77], fill=(245, 245, 245))
    elif shape == 'lamp':
        d.polygon([(w * 0.36, h * 0.18), (w * 0.64, h * 0.18), (w * 0.74, h * 0.44), (w * 0.26, h * 0.44)], fill=colour)
        d.rectangle([w * 0.485, h * 0.44, w * 0.515, h * 0.76], fill=(80, 80, 90))
        d.ellipse([w * 0.34, h * 0.74, w * 0.66, h * 0.82], fill=(80, 80, 90))
    elif shape == 'plant':
        d.polygon([(w * 0.34, h * 0.56), (w * 0.66, h * 0.56), (w * 0.62, h * 0.82), (w * 0.38, h * 0.82)], fill=colour)
        for i in range(7):
            a = math.pi * (0.15 + i * 0.12)
            x = w * 0.5 + math.cos(a) * w * 0.2
            y = h * 0.5 - math.sin(a) * h * 0.26
            d.ellipse([x - w * 0.07, y - h * 0.035, x + w * 0.07, y + h * 0.035], fill=(70 + i * 8, 150, 90))
        d.line([(w * 0.5, h * 0.56), (w * 0.5, h * 0.3)], fill=(70, 130, 80), width=6)
    elif shape == 'watch':
        d.rectangle([w * 0.42, h * 0.14, w * 0.58, h * 0.86], fill=dark)
        d.ellipse([w * 0.3, h * 0.3, w * 0.7, h * 0.7], fill=colour)
        d.ellipse([w * 0.34, h * 0.34, w * 0.66, h * 0.66], fill=(250, 250, 250))
        d.line([(w * 0.5, h * 0.5), (w * 0.5, h * 0.38)], fill=(40, 40, 40), width=6)
        d.line([(w * 0.5, h * 0.5), (w * 0.59, h * 0.54)], fill=(40, 40, 40), width=5)
    elif shape == 'headphone':
        d.arc([w * 0.26, h * 0.2, w * 0.74, h * 0.68], 180, 360, fill=dark, width=int(w * 0.04))
        d.rounded_rectangle([w * 0.22, h * 0.44, w * 0.34, h * 0.7], radius=24, fill=colour)
        d.rounded_rectangle([w * 0.66, h * 0.44, w * 0.78, h * 0.7], radius=24, fill=colour)
    elif shape == 'notebook':
        d.rounded_rectangle([w * 0.28, h * 0.18, w * 0.72, h * 0.82], radius=14, fill=colour)
        d.rectangle([w * 0.28, h * 0.18, w * 0.34, h * 0.82], fill=dark)
        d.rounded_rectangle([w * 0.42, h * 0.3, w * 0.64, h * 0.4], radius=6, fill=(250, 250, 250))
    elif shape == 'cap':
        d.chord([w * 0.24, h * 0.3, w * 0.76, h * 0.78], 180, 360, fill=colour)
        d.polygon([(w * 0.24, h * 0.54), (w * 0.88, h * 0.54), (w * 0.82, h * 0.62), (w * 0.24, h * 0.6)], fill=dark)
    elif shape == 'jar':
        d.rounded_rectangle([w * 0.32, h * 0.3, w * 0.68, h * 0.82], radius=36, fill=colour)
        d.rectangle([w * 0.34, h * 0.22, w * 0.66, h * 0.32], fill=dark)
        d.rectangle([w * 0.32, h * 0.46, w * 0.68, h * 0.66], fill=(250, 246, 236))
    else:  # box
        d.polygon([(w * 0.5, h * 0.2), (w * 0.8, h * 0.34), (w * 0.5, h * 0.48), (w * 0.2, h * 0.34)], fill=light)
        d.polygon([(w * 0.2, h * 0.34), (w * 0.5, h * 0.48), (w * 0.5, h * 0.84), (w * 0.2, h * 0.7)], fill=colour)
        d.polygon([(w * 0.8, h * 0.34), (w * 0.5, h * 0.48), (w * 0.5, h * 0.84), (w * 0.8, h * 0.7)], fill=dark)
    label = item.get('label') or ''
    if label:
        # มุมบนขวา — มุมบนซ้ายเป็นที่ของป้าย "ใหม่"/"หมด" ที่ widget product วางทับรูป
        size = max(18, h // 34)
        box_w = text_width(label, size) + int(size * 0.55) * 2
        chip(img, label, (int(w * 0.95) - box_w, int(h * 0.05)), size, darker(p[0], 0.1), (255, 255, 255))
    d = ImageDraw.Draw(img)
    size, lines = fit_lines(item.get('caption', ''), w * 0.9, max(26, h // 26), 16, max_lines=1)
    tw = text_width(lines[0], size)
    draw_text(d, ((w - tw) / 2, h * 0.9), lines[0], size, (70, 70, 80))
    return img


# ---------------------------------------------------------------------------
# เอกสาร
# ---------------------------------------------------------------------------
def document_page(item, palette, site_label):
    """หน้ากระดาษ A4 (96 dpi) — หัวเรื่อง ข้อความจริงจาก 'lines' และหมายเหตุ"""
    w, h = 794, 1123
    img = Image.new('RGB', (w, h), (255, 255, 255))
    d = ImageDraw.Draw(img)
    c1 = rgb(palette[0])
    d.rectangle([0, 0, w, 96], fill=c1)
    draw_text(d, (56, 30), site_label, 26, (255, 255, 255))
    label = item.get('label') or ''
    if label:
        lw = text_width(label, 16, False)
        draw_text(d, (w - 56 - lw, 38), label, 16, (235, 240, 250), bold=False)
    y = 140
    size, lines = fit_lines(item.get('caption', ''), w - 112, 30, 20, max_lines=3)
    for line in lines:
        draw_text(d, (56, y), line, size, (30, 30, 40))
        y += int(size * 1.5)
    d.line([(56, y + 8), (w - 56, y + 8)], fill=lighter(c1, 0.4), width=3)
    y += 36
    body = item.get('lines') or []
    for para in body:
        for line in wrap(para, 18, w - 112, bold=False, max_lines=8):
            draw_text(d, (56, y), line, 18, (55, 55, 65), bold=False)
            y += 32
        y += 14
    rng = random.Random(item.get('seed', 1))
    while y < h - 220:
        d.rounded_rectangle([56, y + 8, 56 + rng.randint(420, w - 112), y + 20], radius=6, fill=(232, 234, 240))
        y += 34
        if rng.random() < 0.15:
            y += 26
    note = 'เอกสารตัวอย่างจากข้อมูลตัวอย่างของ GCMS — แทนที่ด้วยไฟล์จริงได้ที่หน้าผู้ดูแล'
    draw_text(d, (56, h - 90), note, 15, (130, 130, 140), bold=False)
    return img


def save_pdf(img, target, title):
    fixed = time.gmtime(1767225600)  # 2026-01-01 — วันที่คงที่ ไฟล์จึงเหมือนเดิมทุกครั้ง
    img.save(target, 'PDF', resolution=96.0, quality=72, title=title, author='GCMS', creator='GCMS sample data',
             producer='GCMS', creationDate=fixed, modDate=fixed)


def xml_escape(text):
    return (text.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;').replace('"', '&quot;'))


def zip_write(zf, name, data):
    info = zipfile.ZipInfo(name, date_time=(2026, 1, 1, 0, 0, 0))
    info.compress_type = zipfile.ZIP_DEFLATED
    info.external_attr = 0o644 << 16
    zf.writestr(info, data)


def make_docx(item, target, site_label):
    """DOCX ขั้นต่ำที่ Word/LibreOffice เปิดได้ — ข้อความไทยจริง"""
    def para(text, size=32, bold=False):
        rpr = '<w:rPr><w:rFonts w:ascii="Tahoma" w:hAnsi="Tahoma" w:cs="Tahoma"/>' + ('<w:b/><w:bCs/>' if bold else '') + \
              '<w:sz w:val="%d"/><w:szCs w:val="%d"/><w:lang w:bidi="th-TH"/></w:rPr>' % (size, size)
        return '<w:p><w:pPr><w:spacing w:after="160"/></w:pPr><w:r>%s<w:t xml:space="preserve">%s</w:t></w:r></w:p>' % (rpr, xml_escape(text))

    body = [para(site_label, 24, True), para(item.get('caption', ''), 36, True)]
    for line in item.get('lines') or []:
        body.append(para(line, 28))
    body.append(para('ลงชื่อ ........................................................ ผู้ยื่นแบบฟอร์ม', 28))
    body.append(para('วันที่ ........ เดือน ......................... พ.ศ. ..............', 28))
    body.append(para('เอกสารตัวอย่างจากข้อมูลตัวอย่างของ GCMS — แทนที่ด้วยไฟล์จริงได้ที่หน้าผู้ดูแล', 20))
    document = ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
                + ''.join(body) +
                '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
                '</w:body></w:document>')
    content_types = ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                     '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                     '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                     '<Default Extension="xml" ContentType="application/xml"/>'
                     '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
                     '</Types>')
    rels = ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            '</Relationships>')
    with zipfile.ZipFile(target, 'w') as zf:
        zip_write(zf, '[Content_Types].xml', content_types)
        zip_write(zf, '_rels/.rels', rels)
        zip_write(zf, 'word/document.xml', document)


def make_zip(item, target, palette, site_label):
    """ZIP ที่มี README.txt และ PDF หนึ่งหน้า"""
    page = document_page(item, palette, site_label).resize((496, 702), Image.LANCZOS)
    buf = io.BytesIO()
    fixed = time.gmtime(1767225600)
    page.save(buf, 'PDF', resolution=60.0, quality=70, title=item.get('caption', ''), creationDate=fixed, modDate=fixed, producer='GCMS')
    readme = item.get('caption', '') + '\r\n\r\n' + '\r\n'.join(item.get('lines') or []) + \
        '\r\n\r\nไฟล์ตัวอย่างจากข้อมูลตัวอย่างของ GCMS\r\n'
    with zipfile.ZipFile(target, 'w') as zf:
        zip_write(zf, 'README.txt', readme.encode('utf-8'))
        zip_write(zf, 'sample.pdf', buf.getvalue())


# ---------------------------------------------------------------------------
# main
# ---------------------------------------------------------------------------
def build(key):
    folder = os.path.join(HERE, key)
    with open(os.path.join(folder, 'images.json'), encoding='utf-8') as fh:
        manifest = json.load(fh)
    theme = manifest.get('theme') or {}
    palette = theme.get('palette') or ['#1d4ed8', '#0ea5e9', '#f59e0b', '#10b981', '#ef4444']
    site_label = theme.get('document_label') or theme.get('label') or key
    out_root = os.path.join(folder, 'datas')
    written = 0
    total = 0
    for item in manifest['images']:
        target = os.path.join(out_root, item['file'])
        os.makedirs(os.path.dirname(target), exist_ok=True)
        rng = random.Random(int(item.get('seed') or 1))
        kind = item['kind']
        if kind == 'article':
            make_article(item, palette, rng).save(target, 'WEBP', quality=78, method=6)
        elif kind == 'category':
            make_category(item, palette, rng).save(target, 'WEBP', quality=80, method=6)
        elif kind == 'photo':
            make_photo(item, palette, rng).save(target, 'WEBP', quality=76, method=6)
        elif kind == 'avatar':
            make_avatar(item, palette, rng).save(target, 'WEBP', quality=82, method=6)
        elif kind == 'video':
            make_video(item, palette, rng).save(target, 'JPEG', quality=78, optimize=True)
        elif kind == 'slide':
            make_slide(item, palette, rng).save(target, 'WEBP', quality=80, method=6)
        elif kind == 'banner':
            make_slide(item, palette, rng, compact=True).save(target, 'WEBP', quality=80, method=6)
        elif kind == 'imagemenu':
            make_imagemenu(item, palette, rng).save(target, 'WEBP', quality=85, method=6)
        elif kind == 'portfolio':
            make_portfolio(item, palette, rng).save(target, 'WEBP', quality=80, method=6)
        elif kind == 'product':
            make_product(item, palette, rng).save(target, 'WEBP', quality=80, method=6)
        elif kind == 'pdf':
            save_pdf(document_page(item, palette, site_label), target, item.get('caption', ''))
        elif kind in ('docx', 'doc'):
            make_docx(item, target, site_label)
        elif kind == 'zip':
            make_zip(item, target, palette, site_label)
        else:
            print('unknown kind', kind, 'for', item['file'], file=sys.stderr)
            continue
        written += 1
        total += os.path.getsize(target)
    print('%s: %d file(s) written under install/seeds/%s/datas/ (%.1f MB)' % (key, written, key, total / 1048576.0))


if __name__ == '__main__':
    keys = sys.argv[1:]
    if keys == ['--all']:
        keys = sorted(k for k in os.listdir(HERE) if os.path.isfile(os.path.join(HERE, k, 'images.json')))
    if not keys:
        print('usage: python3 install/seeds/images.py <type> [type …] | --all', file=sys.stderr)
        sys.exit(1)
    for k in keys:
        build(k)
