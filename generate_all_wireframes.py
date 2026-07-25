import os
from PIL import Image, ImageDraw, ImageFont

def draw_rounded_rectangle(draw, xy, radius, fill=None, outline=None, width=1):
    x1, y1, x2, y2 = xy
    draw.rounded_rectangle([x1, y1, x2, y2], radius=radius, fill=fill, outline=outline, width=width)

def create_base_canvas():
    W, H = 1600, 2200
    img = Image.new('RGB', (W, H), color='#F8FAFC')
    draw = ImageDraw.Draw(img)
    return img, draw, W, H

def get_fonts():
    font_path = "C:/Windows/Fonts/arial.ttf"
    font_bd_path = "C:/Windows/Fonts/arialbd.ttf"
    return {
        'title': ImageFont.truetype(font_bd_path, 36),
        'header': ImageFont.truetype(font_bd_path, 26),
        'sub': ImageFont.truetype(font_path, 18),
        'body': ImageFont.truetype(font_path, 16),
        'bold': ImageFont.truetype(font_bd_path, 16),
        'small': ImageFont.truetype(font_path, 14),
        'badge': ImageFont.truetype(font_bd_path, 13)
    }

colors = {
    'bg': '#F8FAFC',
    'slate_900': '#0F172A',
    'slate_800': '#1E293B',
    'slate_600': '#475569',
    'slate_400': '#94A3B8',
    'slate_200': '#E2E8F0',
    'slate_100': '#F1F5F9',
    'indigo_600': '#4F46E5',
    'indigo_50': '#EEF2FF',
    'white': '#FFFFFF',
    'green_600': '#16A34A',
    'amber_500': '#F59E0B'
}

def draw_common_header(draw, W, title_page, active_nav="Home"):
    f = get_fonts()
    c = colors
    # Page Header Info
    draw.text((60, 40), f"StudentHub Wireframe: {title_page}", fill=c['slate_900'], font=f['title'])
    draw.text((60, 85), "Low-Fidelity Visual UI Layout Skeleton", fill=c['slate_600'], font=f['sub'])
    
    # Outer Wireframe Container Frame
    x1, y1, x2, y2 = 60, 130, W - 60, 2140
    draw_rounded_rectangle(draw, (x1, y1, x2, y2), radius=16, fill=c['white'], outline=c['slate_900'], width=3)
    
    # Top Navigation Bar inside frame
    nav_y1 = y1 + 20
    nav_y2 = nav_y1 + 70
    draw_rounded_rectangle(draw, (x1 + 20, nav_y1, x2 - 20, nav_y2), radius=10, fill=c['white'], outline=c['slate_400'], width=2)
    draw.text((x1 + 40, nav_y1 + 35), "[Logo] StudentHub", fill=c['indigo_600'], font=f['header'], anchor='lm')
    
    nav_items = ["Home", "About", "Events", "FAQ", "Feedback", "Contact"]
    nav_x_start = x1 + 350
    for i, item in enumerate(nav_items):
        item_x = nav_x_start + i * 110
        color = c['indigo_600'] if item == active_nav else c['slate_800']
        font = f['bold'] if item == active_nav else f['body']
        draw.text((item_x, nav_y1 + 35), item, fill=color, font=font, anchor='lm')
    
    # User Login Button
    draw_rounded_rectangle(draw, (x2 - 180, nav_y1 + 15, x2 - 35, nav_y2 - 15), radius=6, fill=c['indigo_600'])
    draw.text((x2 - 107, nav_y1 + 35), "User Login", fill=c['white'], font=f['body'], anchor='mm')
    
    # Footer inside frame
    foot_y1 = y2 - 70
    draw_rounded_rectangle(draw, (x1 + 20, foot_y1, x2 - 20, y2 - 20), radius=10, fill=c['slate_800'])
    draw.text((W//2, foot_y1 + 25), "© 2026 StudentHub Portal — CHARUSAT DEPSTAR (ITUE203)", fill=c['white'], font=f['small'], anchor='mm')
    
    return x1 + 20, nav_y2 + 20, x2 - 20, foot_y1 - 20

# --- PAGE 1: INDEX ---
def generate_index():
    img, draw, W, H = create_base_canvas()
    f, c = get_fonts(), colors
    fx1, fy1, fx2, fy2 = draw_common_header(draw, W, "Home Page (index.html)", active_nav="Home")
    
    cy = fy1
    # Hero Banner
    draw_rounded_rectangle(draw, (fx1, cy, fx2, cy + 320), radius=12, fill=c['slate_100'], outline=c['slate_400'], width=2)
    draw.text((W//2, cy + 70), "WELCOME TO STUDENTHUB PORTAL", fill=c['slate_900'], font=f['title'], anchor='mm')
    draw.text((W//2, cy + 120), "Your central web portal for campus news, events & academic profile.", fill=c['slate_600'], font=f['sub'], anchor='mm')
    
    draw_rounded_rectangle(draw, (W//2 - 220, cy + 200, W//2 - 20, cy + 255), radius=8, fill=c['indigo_600'])
    draw.text((W//2 - 120, cy + 227), "Explore Events", fill=c['white'], font=f['bold'], anchor='mm')
    
    draw_rounded_rectangle(draw, (W//2 + 20, cy + 200, W//2 + 220, cy + 255), radius=8, fill=c['white'], outline=c['slate_800'], width=2)
    draw.text((W//2 + 120, cy + 227), "Student Login", fill=c['slate_900'], font=f['bold'], anchor='mm')
    cy += 350
    
    # Stats Row
    sw = (fx2 - fx1 - 40) // 3
    for i, (val, lbl) in enumerate([("10,000+", "Enrolled Students"), ("50+", "Active Events"), ("100%", "Digital Campus")]):
        sx1 = fx1 + i * (sw + 20)
        draw_rounded_rectangle(draw, (sx1, cy, sx1 + sw, cy + 120), radius=10, fill=c['white'], outline=c['slate_400'], width=2)
        draw.text((sx1 + sw//2, cy + 40), val, fill=c['indigo_600'], font=f['header'], anchor='mm')
        draw.text((sx1 + sw//2, cy + 80), lbl, fill=c['slate_600'], font=f['body'], anchor='mm')
    cy += 150
    
    # Announcements Section
    draw.text((fx1, cy), "Campus Announcements & News", fill=c['slate_900'], font=f['header'])
    cy += 45
    card_w = (fx2 - fx1 - 20) // 2
    for i, (ntitle, ndate) in enumerate([("Mid-Term Exam Schedule Released", "July 25, 2026"), ("TechFest 2026 Registration Open", "July 28, 2026")]):
        cx1 = fx1 + i * (card_w + 20)
        draw_rounded_rectangle(draw, (cx1, cy, cx1 + card_w, cy + 340), radius=10, fill=c['white'], outline=c['slate_600'], width=2)
        # Image box
        draw_rounded_rectangle(draw, (cx1 + 15, cy + 15, cx1 + card_w - 15, cy + 160), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
        draw.line([(cx1 + 15, cy + 15), (cx1 + card_w - 15, cy + 160)], fill=c['slate_400'], width=2)
        draw.line([(cx1 + 15, cy + 160), (cx1 + card_w - 15, cy + 15)], fill=c['slate_400'], width=2)
        draw.text((cx1 + card_w//2, cy + 87), "[ Image Placeholder ]", fill=c['slate_600'], font=f['body'], anchor='mm')
        
        draw.text((cx1 + 20, cy + 185), ntitle, fill=c['slate_900'], font=f['bold'])
        draw.text((cx1 + 20, cy + 220), f"Published: {ndate}", fill=c['indigo_600'], font=f['small'])
        draw.text((cx1 + 20, cy + 250), "Click to view official circular & guidelines...", fill=c['slate_600'], font=f['small'])
        
        draw_rounded_rectangle(draw, (cx1 + 20, cy + 285, cx1 + 140, cy + 320), radius=6, fill=c['slate_100'], outline=c['slate_600'], width=1)
        draw.text((cx1 + 80, cy + 302), "Read More", fill=c['slate_800'], font=f['small'], anchor='mm')
    cy += 370
    
    # Quick Links Grid
    draw_rounded_rectangle(draw, (fx1, cy, fx2, cy + 240), radius=10, fill=c['slate_100'], outline=c['slate_400'], width=2)
    draw.text((fx1 + 30, cy + 30), "Quick Student Resources", fill=c['slate_900'], font=f['header'])
    links = ["• Academic Calendar", "• Exam Results Portal", "• Library Catalogue", "• Student Support Desk", "• Hostel Portal", "• Transport Schedule"]
    for i, ltxt in enumerate(links):
        lx = fx1 + 30 + (i % 2) * 650
        ly = cy + 80 + (i // 2) * 45
        draw.text((lx, ly), ltxt, fill=c['indigo_600'], font=f['sub'])
    
    return img

# --- PAGE 2: ABOUT ---
def generate_about():
    img, draw, W, H = create_base_canvas()
    f, c = get_fonts(), colors
    fx1, fy1, fx2, fy2 = draw_common_header(draw, W, "About Us (about.html)", active_nav="About")
    
    cy = fy1
    # Hero Banner
    draw_rounded_rectangle(draw, (fx1, cy, fx2, cy + 200), radius=12, fill=c['indigo_50'], outline=c['indigo_600'], width=2)
    draw.text((W//2, cy + 60), "ABOUT STUDENTHUB & DEPSTAR CHARUSAT", fill=c['slate_900'], font=f['title'], anchor='mm')
    draw.text((W//2, cy + 115), "Empowering campus connectivity, digital governance, and academic excellence.", fill=c['slate_600'], font=f['sub'], anchor='mm')
    cy += 230
    
    # Vision & Mission
    card_w = (fx2 - fx1 - 20) // 2
    # Vision
    draw_rounded_rectangle(draw, (fx1, cy, fx1 + card_w, cy + 280), radius=10, fill=c['white'], outline=c['slate_600'], width=2)
    draw.text((fx1 + 25, cy + 25), "🎯 Our Vision", fill=c['indigo_600'], font=f['header'])
    draw.text((fx1 + 25, cy + 75), "To create a seamless, integrated digital ecosystem where students,", fill=c['slate_800'], font=f['body'])
    draw.text((fx1 + 25, cy + 110), "faculty, and administration collaborate efficiently with transparent access", fill=c['slate_800'], font=f['body'])
    draw.text((fx1 + 25, cy + 145), "to academic resources, updates, and campus events.", fill=c['slate_800'], font=f['body'])
    
    # Mission
    draw_rounded_rectangle(draw, (fx1 + card_w + 20, cy, fx2, cy + 280), radius=10, fill=c['white'], outline=c['slate_600'], width=2)
    draw.text((fx1 + card_w + 45, cy + 25), "🚀 Our Mission", fill=c['indigo_600'], font=f['header'])
    draw.text((fx1 + card_w + 45, cy + 75), "Provide a high-speed, secure, and accessible platform built using", fill=c['slate_800'], font=f['body'])
    draw.text((fx1 + card_w + 45, cy + 110), "semantic web standards, responsive design, and robust role-based", fill=c['slate_800'], font=f['body'])
    draw.text((fx1 + card_w + 45, cy + 145), "access control for the CHARUSAT community.", fill=c['slate_800'], font=f['body'])
    cy += 310
    
    # Key Objectives Grid
    draw.text((fx1, cy), "Core Objectives & Features", fill=c['slate_900'], font=f['header'])
    cy += 45
    obj_w = (fx2 - fx1 - 40) // 3
    objs = [
        ("Unified Access", "Single portal for courses, grades, notices & support."),
        ("Real-Time Events", "Live calendar, registration & instant notifications."),
        ("Role Security", "Strict data privacy for Students, Admins & Guests.")
    ]
    for i, (ot, od) in enumerate(objs):
        ox = fx1 + i * (obj_w + 20)
        draw_rounded_rectangle(draw, (ox, cy, ox + obj_w, cy + 160), radius=10, fill=c['slate_100'], outline=c['slate_400'], width=2)
        draw.text((ox + 20, cy + 30), ot, fill=c['indigo_600'], font=f['bold'])
        draw.text((ox + 20, cy + 75), od, fill=c['slate_600'], font=f['small'])
    cy += 190

    # Team Section
    draw.text((fx1, cy), "Faculty & Portal Steering Team", fill=c['slate_900'], font=f['header'])
    cy += 45
    tm_w = (fx2 - fx1 - 40) // 3
    team = [
        ("Dr. A. Patel", "Head of Department (IT)", "Faculty Supervisor"),
        ("Prof. M. Shah", "Web Dev Coordinator", "Lead Architect"),
        ("Student Team", "ITUE203 Batch 2026", "Developers & Designers")
    ]
    for i, (tn, tr, td) in enumerate(team):
        tx = fx1 + i * (tm_w + 20)
        draw_rounded_rectangle(draw, (tx, cy, tx + tm_w, cy + 220), radius=10, fill=c['white'], outline=c['slate_600'], width=2)
        # Avatar placeholder
        draw.ellipse((tx + tm_w//2 - 30, cy + 20, tx + tm_w//2 + 30, cy + 80), fill=c['slate_100'], outline=c['slate_400'], width=2)
        draw.text((tx + tm_w//2, cy + 50), "👤", fill=c['indigo_600'], font=f['header'], anchor='mm')
        draw.text((tx + tm_w//2, cy + 110), tn, fill=c['slate_900'], font=f['bold'], anchor='mm')
        draw.text((tx + tm_w//2, cy + 140), tr, fill=c['indigo_600'], font=f['small'], anchor='mm')
        draw.text((tx + tm_w//2, cy + 170), td, fill=c['slate_600'], font=f['small'], anchor='mm')
    
    return img

# --- PAGE 3: REGISTER ---
def generate_register():
    img, draw, W, H = create_base_canvas()
    f, c = get_fonts(), colors
    fx1, fy1, fx2, fy2 = draw_common_header(draw, W, "Student Registration (register.html)", active_nav="Home")
    
    cy = fy1
    form_w = 1000
    form_x1 = (W - form_w) // 2
    form_x2 = form_x1 + form_w
    
    draw_rounded_rectangle(draw, (form_x1, cy, form_x2, fy2 - 20), radius=14, fill=c['white'], outline=c['slate_800'], width=3)
    fy = cy + 30
    draw.text((W//2, fy), "STUDENT REGISTRATION FORM", fill=c['slate_900'], font=f['header'], anchor='mm')
    draw.text((W//2, fy + 35), "Fill in your authentic academic details to create your StudentHub account.", fill=c['slate_600'], font=f['sub'], anchor='mm')
    fy += 80
    
    # Inputs
    hw = (form_w - 120) // 2
    
    # First & Last Name
    draw.text((form_x1 + 40, fy), "First Name *", fill=c['slate_900'], font=f['bold'])
    draw.text((form_x1 + 60 + hw, fy), "Last Name *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (form_x1 + 40, fy, form_x1 + 40 + hw, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((form_x1 + 55, fy + 22), "e.g. Aarav", fill=c['slate_400'], font=f['body'], anchor='lm')
    draw_rounded_rectangle(draw, (form_x1 + 60 + hw, fy, form_x2 - 40, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((form_x1 + 75 + hw, fy + 22), "e.g. Patel", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 70
    
    # Email
    draw.text((form_x1 + 40, fy), "Email Address (CHARUSAT Domain) *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (form_x1 + 40, fy, form_x2 - 40, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((form_x1 + 55, fy + 22), "aarav.p@charusat.edu.in", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 70
    
    # Password & Confirm
    draw.text((form_x1 + 40, fy), "Password *", fill=c['slate_900'], font=f['bold'])
    draw.text((form_x1 + 60 + hw, fy), "Confirm Password *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (form_x1 + 40, fy, form_x1 + 40 + hw, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((form_x1 + 55, fy + 22), "••••••••••••", fill=c['slate_400'], font=f['body'], anchor='lm')
    draw_rounded_rectangle(draw, (form_x1 + 60 + hw, fy, form_x2 - 40, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((form_x1 + 75 + hw, fy + 22), "••••••••••••", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 70

    # Student ID
    draw.text((form_x1 + 40, fy), "Student ID / Enrollment Number *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (form_x1 + 40, fy, form_x2 - 40, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((form_x1 + 55, fy + 22), "e.g. 26IT001", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 70
    
    # Gender Radios
    draw.text((form_x1 + 40, fy), "Gender *", fill=c['slate_900'], font=f['bold'])
    fy += 30
    draw.ellipse((form_x1 + 40, fy, form_x1 + 60, fy + 20), outline=c['indigo_600'], width=2)
    draw.ellipse((form_x1 + 45, fy + 5, form_x1 + 55, fy + 15), fill=c['indigo_600'])
    draw.text((form_x1 + 70, fy + 10), "Male", fill=c['slate_900'], font=f['body'], anchor='lm')
    
    draw.ellipse((form_x1 + 180, fy, form_x1 + 200, fy + 20), outline=c['slate_600'], width=2)
    draw.text((form_x1 + 210, fy + 10), "Female", fill=c['slate_900'], font=f['body'], anchor='lm')
    
    draw.ellipse((form_x1 + 320, fy, form_x1 + 340, fy + 20), outline=c['slate_600'], width=2)
    draw.text((form_x1 + 350, fy + 10), "Other", fill=c['slate_900'], font=f['body'], anchor='lm')
    fy += 55

    # Course Dropdown
    draw.text((form_x1 + 40, fy), "Enrolled Course / Department *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (form_x1 + 40, fy, form_x2 - 40, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((form_x1 + 55, fy + 22), "B.Tech Information Technology (IT)", fill=c['slate_900'], font=f['body'], anchor='lm')
    draw.text((form_x2 - 70, fy + 22), "▼", fill=c['slate_600'], font=f['body'], anchor='mm')
    fy += 70

    # File Upload
    draw.text((form_x1 + 40, fy), "Upload Student ID Proof (PDF/PNG/JPG) *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (form_x1 + 40, fy, form_x2 - 40, fy + 80), radius=8, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((W//2, fy + 30), "[ Choose File ]  No file chosen", fill=c['slate_600'], font=f['body'], anchor='mm')
    draw.text((W//2, fy + 55), "Max file size: 5MB", fill=c['slate_400'], font=f['small'], anchor='mm')
    fy += 105

    # Checkbox
    draw_rounded_rectangle(draw, (form_x1 + 40, fy, form_x1 + 60, fy + 20), radius=4, fill=c['indigo_600'])
    draw.text((form_x1 + 50, fy + 10), "✓", fill=c['white'], font=f['small'], anchor='mm')
    draw.text((form_x1 + 75, fy + 10), "I agree to the StudentHub terms, privacy policy & code of conduct.", fill=c['slate_900'], font=f['body'], anchor='lm')
    fy += 55

    # Submit Button
    draw_rounded_rectangle(draw, (form_x1 + 40, fy, form_x2 - 40, fy + 55), radius=8, fill=c['indigo_600'])
    draw.text((W//2, fy + 27), "Register Account", fill=c['white'], font=f['header'], anchor='mm')
    
    return img

# --- PAGE 4: LOGIN ---
def generate_login():
    img, draw, W, H = create_base_canvas()
    f, c = get_fonts(), colors
    fx1, fy1, fx2, fy2 = draw_common_header(draw, W, "User Login (login.html)", active_nav="Home")
    
    box_w = 800
    box_x1 = (W - box_w) // 2
    box_x2 = box_x1 + box_w
    cy = fy1 + 100
    
    draw_rounded_rectangle(draw, (box_x1, cy, box_x2, cy + 720), radius=16, fill=c['white'], outline=c['slate_800'], width=3)
    fy = cy + 40
    
    # Icon + Title
    draw.ellipse((W//2 - 35, fy, W//2 + 35, fy + 70), fill=c['indigo_50'], outline=c['indigo_600'], width=2)
    draw.text((W//2, fy + 35), "🔒", fill=c['indigo_600'], font=f['title'], anchor='mm')
    fy += 90
    
    draw.text((W//2, fy), "USER AUTHENTICATION PORTAL", fill=c['slate_900'], font=f['header'], anchor='mm')
    draw.text((W//2, fy + 35), "Sign in with your institutional credentials to access your portal.", fill=c['slate_600'], font=f['sub'], anchor='mm')
    fy += 80

    # Role Selector Pills
    draw.text((box_x1 + 50, fy), "Select User Role:", fill=c['slate_900'], font=f['bold'])
    fy += 25
    rw = (box_w - 140) // 3
    roles = [("Student", True), ("Administrator", False), ("Guest Visitor", False)]
    for i, (rlabel, is_act) in enumerate(roles):
        rx = box_x1 + 50 + i * (rw + 20)
        bg = c['indigo_600'] if is_act else c['slate_100']
        tc = c['white'] if is_act else c['slate_800']
        draw_rounded_rectangle(draw, (rx, fy, rx + rw, fy + 45), radius=8, fill=bg, outline=c['slate_400'], width=1)
        draw.text((rx + rw//2, fy + 22), rlabel, fill=tc, font=f['bold'], anchor='mm')
    fy += 75

    # Username Field
    draw.text((box_x1 + 50, fy), "Username / Institutional Email *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (box_x1 + 50, fy, box_x2 - 50, fy + 50), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((box_x1 + 70, fy + 25), "e.g. aarav.p@charusat.edu.in", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 80

    # Password Field
    draw.text((box_x1 + 50, fy), "Password *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (box_x1 + 50, fy, box_x2 - 50, fy + 50), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((box_x1 + 70, fy + 25), "••••••••••••••••", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 75

    # Remember & Forgot
    draw_rounded_rectangle(draw, (box_x1 + 50, fy, box_x1 + 70, fy + 20), radius=4, fill=c['indigo_600'])
    draw.text((box_x1 + 60, fy + 10), "✓", fill=c['white'], font=f['small'], anchor='mm')
    draw.text((box_x1 + 80, fy + 10), "Remember Me", fill=c['slate_900'], font=f['body'], anchor='lm')
    draw.text((box_x2 - 50, fy + 10), "Forgot Password?", fill=c['indigo_600'], font=f['bold'], anchor='rm')
    fy += 55

    # Submit Button
    draw_rounded_rectangle(draw, (box_x1 + 50, fy, box_x2 - 50, fy + 55), radius=8, fill=c['indigo_600'])
    draw.text((W//2, fy + 27), "Sign In to Portal", fill=c['white'], font=f['header'], anchor='mm')
    fy += 85

    draw.text((W//2, fy), "New Student? Create a new account here", fill=c['indigo_600'], font=f['sub'], anchor='mm')
    
    return img

# --- PAGE 5: DASHBOARD ---
def generate_dashboard():
    img, draw, W, H = create_base_canvas()
    f, c = get_fonts(), colors
    
    draw.text((60, 40), "StudentHub Wireframe: Student Dashboard (dashboard.html)", fill=c['slate_900'], font=f['title'])
    draw.text((60, 85), "Low-Fidelity Visual UI Layout Skeleton", fill=c['slate_600'], font=f['sub'])
    
    x1, y1, x2, y2 = 60, 130, W - 60, 2140
    draw_rounded_rectangle(draw, (x1, y1, x2, y2), radius=16, fill=c['white'], outline=c['slate_900'], width=3)
    
    # Dashboard Nav Header
    draw_rounded_rectangle(draw, (x1 + 20, y1 + 20, x2 - 20, y1 + 90), radius=10, fill=c['white'], outline=c['slate_400'], width=2)
    draw.text((x1 + 40, y1 + 55), "[Logo] StudentHub", fill=c['indigo_600'], font=f['header'], anchor='lm')
    
    # Search
    draw_rounded_rectangle(draw, (x1 + 350, y1 + 35, x1 + 800, y1 + 75), radius=8, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((x1 + 370, y1 + 55), "Search courses, notices, events...", fill=c['slate_400'], font=f['body'], anchor='lm')
    
    draw.text((x2 - 220, y1 + 55), "🔔 (3)", fill=c['slate_900'], font=f['bold'], anchor='rm')
    draw_rounded_rectangle(draw, (x2 - 180, y1 + 35, x2 - 40, y1 + 75), radius=20, fill=c['indigo_50'], outline=c['indigo_600'], width=2)
    draw.text((x2 - 110, y1 + 55), "Aarav P. 👤", fill=c['indigo_600'], font=f['bold'], anchor='mm')
    
    cy = y1 + 110
    sb_w = 280
    main_x1 = x1 + 20 + sb_w + 20
    main_w = (x2 - 20) - main_x1
    
    # Sidebar
    draw_rounded_rectangle(draw, (x1 + 20, cy, x1 + 20 + sb_w, y2 - 90), radius=12, fill=c['slate_100'], outline=c['slate_400'], width=2)
    sb_items = [("Dashboard", True), ("My Courses", False), ("Grades & GPA", False), ("Class Schedule", False), ("Campus Events", False), ("Profile Settings", False), ("Support Desk", False)]
    for i, (lbl, is_a) in enumerate(sb_items):
        iy = cy + 20 + i * 65
        if is_a:
            draw_rounded_rectangle(draw, (x1 + 30, iy, x1 + 10 + sb_w, iy + 50), radius=8, fill=c['indigo_600'])
            draw.text((x1 + 50, iy + 25), f"• {lbl}", fill=c['white'], font=f['bold'], anchor='lm')
        else:
            draw.text((x1 + 50, iy + 25), f"• {lbl}", fill=c['slate_800'], font=f['body'], anchor='lm')
            
    # Main Content Workspace
    # Welcome banner
    draw_rounded_rectangle(draw, (main_x1, cy, main_x1 + main_w, cy + 160), radius=12, fill=c['indigo_50'], outline=c['indigo_600'], width=2)
    draw.text((main_x1 + 30, cy + 45), "Welcome back, Aarav Patel!", fill=c['slate_900'], font=f['header'])
    draw.text((main_x1 + 30, cy + 90), "Dept: B.Tech Information Technology  |  Semester 3 (2026-27 ODD)", fill=c['slate_600'], font=f['sub'])
    draw.text((main_x1 + 30, cy + 125), "Last Login: July 25, 2026 at 10:15 AM", fill=c['indigo_600'], font=f['small'])
    cy += 185
    
    # 3 Stat Cards
    c_w = (main_w - 40) // 3
    for i, (sv, sl) in enumerate([("5 Active", "Enrolled Courses"), ("3.85 / 4.0", "Current GPA"), ("3 Events", "Upcoming RSVPs")]):
        cx = main_x1 + i * (c_w + 20)
        draw_rounded_rectangle(draw, (cx, cy, cx + c_w, cy + 120), radius=10, fill=c['white'], outline=c['slate_400'], width=2)
        draw.text((cx + c_w//2, cy + 40), sv, fill=c['indigo_600'], font=f['header'], anchor='mm')
        draw.text((cx + c_w//2, cy + 80), sl, fill=c['slate_600'], font=f['small'], anchor='mm')
    cy += 145
    
    # Enrolled Courses
    draw.text((main_x1, cy), "Enrolled Courses", fill=c['slate_900'], font=f['header'])
    cy += 40
    courses = [("Web Dev Frameworks", "ITUE203", 75), ("Data Structures", "ITUE201", 60), ("Database Systems", "ITUE204", 80)]
    for i, (ct, cc, cp) in enumerate(courses):
        cx = main_x1 + i * (c_w + 20)
        draw_rounded_rectangle(draw, (cx, cy, cx + c_w, cy + 200), radius=10, fill=c['white'], outline=c['slate_600'], width=2)
        draw_rounded_rectangle(draw, (cx, cy, cx + c_w, cy + 50), radius=10, fill=c['slate_100'], outline=c['slate_400'], width=1)
        draw.text((cx + 15, cy + 25), cc, fill=c['indigo_600'], font=f['bold'], anchor='lm')
        draw.text((cx + 15, cy + 85), ct, fill=c['slate_900'], font=f['bold'])
        draw.text((cx + 15, cy + 120), f"Progress: {cp}%", fill=c['slate_600'], font=f['small'])
        
        draw_rounded_rectangle(draw, (cx + 15, cy + 150, cx + c_w - 15, cy + 170), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
        draw_rounded_rectangle(draw, (cx + 15, cy + 150, cx + 15 + int((c_w - 30) * (cp/100)), cy + 170), radius=6, fill=c['indigo_600'])
    cy += 230

    # Side-by-side Notifs & Events
    pw = (main_w - 20) // 2
    # Left Notifs
    draw_rounded_rectangle(draw, (main_x1, cy, main_x1 + pw, cy + 380), radius=10, fill=c['white'], outline=c['slate_400'], width=2)
    draw.text((main_x1 + 20, cy + 30), "Recent Notifications", fill=c['slate_900'], font=f['header'])
    notifs = [("Practical 2 Submission", "3 mins ago"), ("Mid-Term Exam Allocations", "1 hr ago"), ("Library Book Return Notice", "Yesterday")]
    for i, (nt, ntm) in enumerate(notifs):
        ny = cy + 70 + i * 95
        draw_rounded_rectangle(draw, (main_x1 + 15, ny, main_x1 + pw - 15, ny + 80), radius=8, fill=c['slate_100'])
        draw.text((main_x1 + 25, ny + 25), nt, fill=c['slate_900'], font=f['bold'])
        draw.text((main_x1 + 25, ny + 52), f"Time: {ntm}", fill=c['indigo_600'], font=f['small'])

    # Right Events
    draw_rounded_rectangle(draw, (main_x1 + pw + 20, cy, main_x1 + main_w, cy + 380), radius=10, fill=c['white'], outline=c['slate_400'], width=2)
    draw.text((main_x1 + pw + 40, cy + 30), "Upcoming Events Schedule", fill=c['slate_900'], font=f['header'])
    events_list = [("CHARUSAT Hackathon 2026", "Aug 5, 2026"), ("Web Dev Expert Lecture", "Aug 12, 2026"), ("Campus Sports Meet 2026", "Aug 20, 2026")]
    for i, (et, ed) in enumerate(events_list):
        ey = cy + 70 + i * 95
        draw_rounded_rectangle(draw, (main_x1 + pw + 35, ey, main_x1 + main_w - 15, ey + 80), radius=8, fill=c['slate_100'])
        draw.text((main_x1 + pw + 45, ey + 25), et, fill=c['slate_900'], font=f['bold'])
        draw.text((main_x1 + pw + 45, ey + 52), f"Date: {ed}", fill=c['indigo_600'], font=f['small'])

    # Footer inside frame
    foot_y1 = y2 - 70
    draw_rounded_rectangle(draw, (x1 + 20, foot_y1, x2 - 20, y2 - 20), radius=10, fill=c['slate_800'])
    draw.text((W//2, foot_y1 + 25), "© 2026 StudentHub Portal — CHARUSAT DEPSTAR (ITUE203)", fill=c['white'], font=f['small'], anchor='mm')
    
    return img

# --- PAGE 6: EVENTS ---
def generate_events():
    img, draw, W, H = create_base_canvas()
    f, c = get_fonts(), colors
    fx1, fy1, fx2, fy2 = draw_common_header(draw, W, "Campus Events (events.html)", active_nav="Events")
    
    cy = fy1
    # Hero Banner
    draw_rounded_rectangle(draw, (fx1, cy, fx2, cy + 180), radius=12, fill=c['slate_100'], outline=c['slate_400'], width=2)
    draw.text((W//2, cy + 50), "CAMPUS EVENTS & WORKSHOPS", fill=c['slate_900'], font=f['title'], anchor='mm')
    draw.text((W//2, cy + 105), "Discover, RSVP, and participate in technical, cultural, and sports events.", fill=c['slate_600'], font=f['sub'], anchor='mm')
    cy += 200

    # Category Filter Bar
    cats = [("All Events", True), ("Technical", False), ("Sports", False), ("Cultural", False), ("Workshops", False)]
    cw = (fx2 - fx1 - 80) // len(cats)
    for i, (clbl, is_a) in enumerate(cats):
        cx = fx1 + i * (cw + 20)
        bg = c['indigo_600'] if is_a else c['white']
        tc = c['white'] if is_a else c['slate_800']
        draw_rounded_rectangle(draw, (cx, cy, cx + cw, cy + 45), radius=8, fill=bg, outline=c['slate_400'], width=1)
        draw.text((cx + cw//2, cy + 22), clbl, fill=tc, font=f['bold'], anchor='mm')
    cy += 70

    # Featured Event Card
    draw_rounded_rectangle(draw, (fx1, cy, fx2, cy + 320), radius=12, fill=c['indigo_50'], outline=c['indigo_600'], width=2)
    draw.text((fx1 + 30, cy + 35), "⭐ FEATURED EVENT SPOTLIGHT", fill=c['indigo_600'], font=f['bold'])
    draw.text((fx1 + 30, cy + 85), "TechFest 2026 Annual Hackathon", fill=c['slate_900'], font=f['title'])
    draw.text((fx1 + 30, cy + 135), "Date: August 5, 2026  |  Location: Auditorium Hall 1  |  Time: 9:00 AM - 6:00 PM", fill=c['slate_800'], font=f['sub'])
    draw.text((fx1 + 30, cy + 175), "Join 200+ students in building innovative software solutions for campus challenges.", fill=c['slate_600'], font=f['body'])
    
    draw_rounded_rectangle(draw, (fx1 + 30, cy + 230, fx1 + 220, cy + 285), radius=8, fill=c['indigo_600'])
    draw.text((fx1 + 125, cy + 257), "RSVP Now (Seats Left: 15)", fill=c['white'], font=f['bold'], anchor='mm')
    cy += 350

    # Events Grid (4 items)
    draw.text((fx1, cy), "Upcoming Campus Events", fill=c['slate_900'], font=f['header'])
    cy += 45
    card_w = (fx2 - fx1 - 20) // 2
    ev_grid = [
        ("Web Dev Expert Seminar", "Aug 12, 2026", "Tech", "Online (Zoom)"),
        ("Campus Sports Meet 2026", "Aug 20, 2026", "Sports", "Sports Complex"),
        ("Cultural Drama Night", "Sep 05, 2026", "Cultural", "Main Auditorium"),
        ("AI & ML Hands-on Workshop", "Sep 15, 2026", "Workshop", "Computer Lab 3")
    ]
    for i, (et, ed, ec, el) in enumerate(ev_grid):
        gx = fx1 + (i % 2) * (card_w + 20)
        gy = cy + (i // 2) * 260
        draw_rounded_rectangle(draw, (gx, gy, gx + card_w, gy + 240), radius=10, fill=c['white'], outline=c['slate_600'], width=2)
        draw_rounded_rectangle(draw, (gx + 20, gy + 20, gx + 120, gy + 50), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
        draw.text((gx + 70, gy + 35), ec, fill=c['indigo_600'], font=f['bold'], anchor='mm')
        
        draw.text((gx + 140, gy + 35), f"📅 {ed}", fill=c['slate_600'], font=f['body'], anchor='lm')
        draw.text((gx + 20, gy + 85), et, fill=c['slate_900'], font=f['bold'])
        draw.text((gx + 20, gy + 125), f"📍 Location: {el}", fill=c['slate_600'], font=f['small'])
        draw.text((gx + 20, gy + 155), "Registration open for all enrolled students.", fill=c['slate_600'], font=f['small'])
        
        draw_rounded_rectangle(draw, (gx + 20, gy + 185, gx + 150, gy + 220), radius=6, fill=c['indigo_600'])
        draw.text((gx + 85, gy + 202), "Register RSVP", fill=c['white'], font=f['small'], anchor='mm')
        
    return img

# --- PAGE 7: PROFILE ---
def generate_profile():
    img, draw, W, H = create_base_canvas()
    f, c = get_fonts(), colors
    fx1, fy1, fx2, fy2 = draw_common_header(draw, W, "Student Profile (profile.html)", active_nav="Home")
    
    cy = fy1
    # Profile Header Box
    draw_rounded_rectangle(draw, (fx1, cy, fx2, cy + 240), radius=12, fill=c['white'], outline=c['slate_800'], width=2)
    # Avatar Circle
    draw.ellipse((fx1 + 40, cy + 40, fx1 + 180, cy + 180), fill=c['indigo_50'], outline=c['indigo_600'], width=3)
    draw.text((fx1 + 110, cy + 110), "👤", fill=c['indigo_600'], font=f['title'], anchor='mm')
    
    draw.text((fx1 + 220, cy + 50), "Aarav Patel", fill=c['slate_900'], font=f['title'])
    draw.text((fx1 + 220, cy + 100), "Department: B.Tech Information Technology (IT)  |  Enrollment: 26IT001", fill=c['slate_600'], font=f['sub'])
    draw.text((fx1 + 220, cy + 140), "Academic Status: Active Student  |  Semester: 3 (ODD Term)", fill=c['indigo_600'], font=f['bold'])
    
    draw_rounded_rectangle(draw, (fx2 - 200, cy + 40, fx2 - 40, cy + 90), radius=8, fill=c['indigo_600'])
    draw.text((fx2 - 120, cy + 65), "Edit Profile", fill=c['white'], font=f['bold'], anchor='mm')
    cy += 270

    # Tabs
    tabs = [("Personal Details", True), ("Academic Record", False), ("Enrolled Courses", False), ("Security & Password", False)]
    tw = (fx2 - fx1 - 60) // len(tabs)
    for i, (tlbl, is_a) in enumerate(tabs):
        tx = fx1 + i * (tw + 20)
        bg = c['indigo_600'] if is_a else c['slate_100']
        tc = c['white'] if is_a else c['slate_800']
        draw_rounded_rectangle(draw, (tx, cy, tx + tw, cy + 45), radius=8, fill=bg, outline=c['slate_400'], width=1)
        draw.text((tx + tw//2, cy + 22), tlbl, fill=tc, font=f['bold'], anchor='mm')
    cy += 70

    # Detailed Info Grid (2 columns)
    col_w = (fx2 - fx1 - 20) // 2
    # Left Column: Personal
    draw_rounded_rectangle(draw, (fx1, cy, fx1 + col_w, cy + 580), radius=10, fill=c['white'], outline=c['slate_600'], width=2)
    draw.text((fx1 + 25, cy + 25), "Personal Information", fill=c['slate_900'], font=f['header'])
    
    fields = [
        ("Full Name:", "Aarav Patel"),
        ("Email Address:", "aarav.p@charusat.edu.in"),
        ("Contact Number:", "+91 98765 43210"),
        ("Gender:", "Male"),
        ("Date of Birth:", "October 14, 2005"),
        ("Address:", "DEPSTAR Student Block, CHARUSAT Campus, Changa")
    ]
    for i, (label, val) in enumerate(fields):
        fy_i = cy + 80 + i * 80
        draw.text((fx1 + 25, fy_i), label, fill=c['slate_600'], font=f['bold'])
        draw.text((fx1 + 25, fy_i + 25), val, fill=c['slate_900'], font=f['body'])

    # Right Column: Academic Summary Table
    draw_rounded_rectangle(draw, (fx1 + col_w + 20, cy, fx2, cy + 580), radius=10, fill=c['white'], outline=c['slate_600'], width=2)
    draw.text((fx1 + col_w + 45, cy + 25), "Academic Summary & Records", fill=c['slate_900'], font=f['header'])
    
    acad_fields = [
        ("Institutional Roll No:", "26IT001"),
        ("Current Semester CGPA:", "3.85 / 4.00"),
        ("Total Earned Credits:", "42 Credits Completed"),
        ("Faculty Advisor:", "Dr. A. Patel (Head of IT)"),
        ("Attendance Rate:", "94.5% Overall Attendance"),
        ("Library Account Status:", "Cleared (No Pending Fines)")
    ]
    for i, (label, val) in enumerate(acad_fields):
        fy_i = cy + 80 + i * 80
        draw.text((fx1 + col_w + 45, fy_i), label, fill=c['slate_600'], font=f['bold'])
        draw.text((fx1 + col_w + 45, fy_i + 25), val, fill=c['indigo_600'], font=f['body'])

    return img

# --- PAGE 8: CONTACT ---
def generate_contact():
    img, draw, W, H = create_base_canvas()
    f, c = get_fonts(), colors
    fx1, fy1, fx2, fy2 = draw_common_header(draw, W, "Contact Support (contact.html)", active_nav="Contact")
    
    cy = fy1
    # Hero Banner
    draw_rounded_rectangle(draw, (fx1, cy, fx2, cy + 160), radius=12, fill=c['slate_100'], outline=c['slate_400'], width=2)
    draw.text((W//2, cy + 45), "GET IN TOUCH WITH US", fill=c['slate_900'], font=f['title'], anchor='mm')
    draw.text((W//2, cy + 95), "Have questions or need technical assistance? Send us a message or visit our campus.", fill=c['slate_600'], font=f['sub'], anchor='mm')
    cy += 180

    # 4 Info Cards Row
    card_w = (fx2 - fx1 - 60) // 4
    info_cards = [
        ("📞 Phone", "+91 (02697) 265000"),
        ("✉️ Email", "info@charusat.edu.in"),
        ("📍 Location", "DEPSTAR Building, Changa"),
        ("⏰ Hours", "Mon - Sat: 9am - 5pm")
    ]
    for i, (title, val) in enumerate(info_cards):
        cx = fx1 + i * (card_w + 20)
        draw_rounded_rectangle(draw, (cx, cy, cx + card_w, cy + 120), radius=10, fill=c['white'], outline=c['slate_400'], width=2)
        draw.text((cx + card_w//2, cy + 35), title, fill=c['indigo_600'], font=f['bold'], anchor='mm')
        draw.text((cx + card_w//2, cy + 75), val, fill=c['slate_800'], font=f['small'], anchor='mm')
    cy += 150

    # 2 Column Main: Form & Map
    cw = (fx2 - fx1 - 20) // 2
    # Left: Inquiry Form
    draw_rounded_rectangle(draw, (fx1, cy, fx1 + cw, cy + 620), radius=12, fill=c['white'], outline=c['slate_600'], width=2)
    draw.text((fx1 + 25, cy + 25), "Send an Inquiry Message", fill=c['slate_900'], font=f['header'])
    
    fy = cy + 70
    draw.text((fx1 + 25, fy), "Full Name *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (fx1 + 25, fy, fx1 + cw - 25, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((fx1 + 40, fy + 22), "e.g. Aarav Patel", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 65
    
    draw.text((fx1 + 25, fy), "Email Address *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (fx1 + 25, fy, fx1 + cw - 25, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((fx1 + 40, fy + 22), "e.g. aarav.p@charusat.edu.in", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 65

    draw.text((fx1 + 25, fy), "Subject *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (fx1 + 25, fy, fx1 + cw - 25, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((fx1 + 40, fy + 22), "Select Topic (e.g. Registration Query)", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 65

    draw.text((fx1 + 25, fy), "Message Details *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (fx1 + 25, fy, fx1 + cw - 25, fy + 120), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((fx1 + 40, fy + 25), "Type your message or inquiry here...", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 140

    draw_rounded_rectangle(draw, (fx1 + 25, fy, fx1 + cw - 25, fy + 50), radius=8, fill=c['indigo_600'])
    draw.text((fx1 + cw//2, fy + 25), "Send Message", fill=c['white'], font=f['bold'], anchor='mm')

    # Right: Map Placeholder
    draw_rounded_rectangle(draw, (fx1 + cw + 20, cy, fx2, cy + 620), radius=12, fill=c['white'], outline=c['slate_600'], width=2)
    draw.text((fx1 + cw + 45, cy + 25), "Campus Location Map", fill=c['slate_900'], font=f['header'])
    
    # Map Box
    draw_rounded_rectangle(draw, (fx1 + cw + 45, cy + 70, fx2 - 25, cy + 450), radius=10, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.line([(fx1 + cw + 45, cy + 70), (fx2 - 25, cy + 450)], fill=c['slate_400'], width=2)
    draw.line([(fx1 + cw + 45, cy + 450), (fx2 - 25, cy + 70)], fill=c['slate_400'], width=2)
    draw.text(((fx1 + cw + 45 + fx2 - 25)//2, cy + 260), "[ Google Map Location Placeholder ]\nCHARUSAT DEPSTAR Campus, Changa", fill=c['slate_800'], font=f['sub'], anchor='mm')
    
    draw.text((fx1 + cw + 45, cy + 480), "Directions: Located 12 km from Petlad Highway.", fill=c['slate_600'], font=f['body'])
    draw.text((fx1 + cw + 45, cy + 520), "Campus Transport Helpline: +91 98765 00000", fill=c['indigo_600'], font=f['bold'])

    return img

# --- PAGE 9: ADMIN ---
def generate_admin():
    img, draw, W, H = create_base_canvas()
    f, c = get_fonts(), colors
    
    draw.text((60, 40), "StudentHub Wireframe: Admin Portal (admin.html)", fill=c['slate_900'], font=f['title'])
    draw.text((60, 85), "Low-Fidelity Visual UI Layout Skeleton", fill=c['slate_600'], font=f['sub'])
    
    x1, y1, x2, y2 = 60, 130, W - 60, 2140
    draw_rounded_rectangle(draw, (x1, y1, x2, y2), radius=16, fill=c['white'], outline=c['slate_900'], width=3)
    
    # Admin Header
    draw_rounded_rectangle(draw, (x1 + 20, y1 + 20, x2 - 20, y1 + 90), radius=10, fill=c['slate_800'])
    draw.text((x1 + 40, y1 + 55), "[Logo] StudentHub", fill=c['white'], font=f['header'], anchor='lm')
    draw_rounded_rectangle(draw, (x1 + 260, y1 + 35, x1 + 400, y1 + 75), radius=6, fill=c['indigo_600'])
    draw.text((x1 + 330, y1 + 55), "Admin Portal 🛡️", fill=c['white'], font=f['small'], anchor='mm')
    
    draw.text((x2 - 50, y1 + 55), "Logged in as: Dr. R. Patel (SuperAdmin)", fill=c['slate_200'], font=f['body'], anchor='rm')
    
    cy = y1 + 110
    # Stat Cards (4 cards)
    card_w = (x2 - x1 - 100) // 4
    stats = [
        ("1,250", "Registered Students"),
        ("12", "System Admins"),
        ("48", "Active Events"),
        ("99.9%", "System Health")
    ]
    for i, (sv, sl) in enumerate(stats):
        cx = x1 + 20 + i * (card_w + 20)
        draw_rounded_rectangle(draw, (cx, cy, cx + card_w, cy + 120), radius=10, fill=c['white'], outline=c['slate_400'], width=2)
        draw.text((cx + card_w//2, cy + 40), sv, fill=c['indigo_600'], font=f['header'], anchor='mm')
        draw.text((cx + card_w//2, cy + 80), sl, fill=c['slate_600'], font=f['small'], anchor='mm')
    cy += 145

    # Control Bar
    draw_rounded_rectangle(draw, (x1 + 20, cy, x2 - 20, cy + 70), radius=10, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((x1 + 40, cy + 35), "Quick Administrative Actions:", fill=c['slate_900'], font=f['bold'], anchor='lm')
    
    btns = ["+ Add New User", "+ Create Event", "Export Reports CSV", "Security Audit"]
    bx_start = x1 + 340
    for i, btxt in enumerate(btns):
        bx = bx_start + i * 180
        draw_rounded_rectangle(draw, (bx, cy + 15, bx + 160, cy + 55), radius=6, fill=c['indigo_600'])
        draw.text((bx + 80, cy + 35), btxt, fill=c['white'], font=f['small'], anchor='mm')
    cy += 95

    # Registered Users Table Section
    draw.text((x1 + 20, cy), "Registered Users & Security Access Control Table", fill=c['slate_900'], font=f['header'])
    cy += 40
    
    # Table Header Box
    tw = (x2 - x1 - 40)
    draw_rounded_rectangle(draw, (x1 + 20, cy, x2 - 20, cy + 50), radius=8, fill=c['slate_800'])
    cols = [(x1 + 40, "User ID"), (x1 + 180, "Full Name"), (x1 + 450, "Email Address"), (x1 + 800, "Department"), (x1 + 1000, "Role"), (x1 + 1180, "Status"), (x1 + 1340, "Actions")]
    for cx, clbl in cols:
        draw.text((cx, cy + 25), clbl, fill=c['white'], font=f['bold'], anchor='lm')
    cy += 50

    # Rows (5 sample rows)
    rows_data = [
        ("26IT001", "Aarav Patel", "aarav.p@charusat.edu.in", "IT", "Student", "Active"),
        ("ADM001", "Dr. R. Patel", "patel.admin@charusat.edu.in", "IT", "SuperAdmin", "Active"),
        ("26IT042", "Priya Sharma", "priya.s@charusat.edu.in", "IT", "Student", "Active"),
        ("GUEST08", "Sam Wilson", "sam.w@gmail.com", "External", "Visitor", "Pending"),
        ("26IT105", "Rahul Verma", "rahul.v@charusat.edu.in", "CS", "Student", "Active")
    ]
    for r_i, (uid, uname, uemail, udept, urole, ustat) in enumerate(rows_data):
        ry = cy + r_i * 65
        bg = c['white'] if r_i % 2 == 0 else c['slate_100']
        draw_rounded_rectangle(draw, (x1 + 20, ry, x2 - 20, ry + 60), radius=6, fill=bg, outline=c['slate_200'], width=1)
        
        draw.text((x1 + 40, ry + 30), uid, fill=c['indigo_600'], font=f['bold'], anchor='lm')
        draw.text((x1 + 180, ry + 30), uname, fill=c['slate_900'], font=f['body'], anchor='lm')
        draw.text((x1 + 450, ry + 30), uemail, fill=c['slate_600'], font=f['small'], anchor='lm')
        draw.text((x1 + 800, ry + 30), udept, fill=c['slate_800'], font=f['body'], anchor='lm')
        draw.text((x1 + 1000, ry + 30), urole, fill=c['indigo_600'], font=f['bold'], anchor='lm')
        
        stat_col = c['green_600'] if ustat == "Active" else c['amber_500']
        draw.text((x1 + 1180, ry + 30), f"● {ustat}", fill=stat_col, font=f['bold'], anchor='lm')
        
        draw_rounded_rectangle(draw, (x1 + 1340, ry + 15, x1 + 1400, ry + 45), radius=4, fill=c['slate_100'], outline=c['slate_600'], width=1)
        draw.text((x1 + 1370, ry + 30), "Edit", fill=c['slate_800'], font=f['small'], anchor='mm')

    cy += 360

    # Audit Log Stream
    draw_rounded_rectangle(draw, (x1 + 20, cy, x2 - 20, cy + 240), radius=10, fill=c['slate_100'], outline=c['slate_400'], width=2)
    draw.text((x1 + 40, cy + 25), "System Audit Log Stream", fill=c['slate_900'], font=f['header'])
    
    logs = [
        ("[Log #101] User aarav.p@charusat.edu.in registered via Registration Form on July 25, 2026"),
        ("[Log #102] Admin Dr. R. Patel modified security permissions for Role: Student"),
        ("[Log #103] New event 'TechFest 2026' published to Campus Events Listing")
    ]
    for i, ltxt in enumerate(logs):
        draw.text((x1 + 40, cy + 70 + i * 45), f"• {ltxt}", fill=c['slate_600'], font=f['body'])

    # Footer inside frame
    foot_y1 = y2 - 70
    draw_rounded_rectangle(draw, (x1 + 20, foot_y1, x2 - 20, y2 - 20), radius=10, fill=c['slate_800'])
    draw.text((W//2, foot_y1 + 25), "© 2026 StudentHub Admin — CHARUSAT DEPSTAR ITUE203", fill=c['white'], font=f['small'], anchor='mm')
    
    return img

# --- PAGE 10: FAQ ---
def generate_faq():
    img, draw, W, H = create_base_canvas()
    f, c = get_fonts(), colors
    fx1, fy1, fx2, fy2 = draw_common_header(draw, W, "Frequently Asked Questions (faq.html)", active_nav="FAQ")
    
    cy = fy1
    # Hero Banner
    draw_rounded_rectangle(draw, (fx1, cy, fx2, cy + 180), radius=12, fill=c['indigo_50'], outline=c['indigo_600'], width=2)
    draw.text((W//2, cy + 50), "FREQUENTLY ASKED QUESTIONS", fill=c['slate_900'], font=f['title'], anchor='mm')
    draw.text((W//2, cy + 105), "Find quick answers to common questions about StudentHub, registration, and courses.", fill=c['slate_600'], font=f['sub'], anchor='mm')
    cy += 200

    # Search Bar
    draw_rounded_rectangle(draw, (fx1, cy, fx2, cy + 60), radius=10, fill=c['white'], outline=c['slate_400'], width=2)
    draw.text((fx1 + 30, cy + 30), "🔍 Search questions, topics, or keywords...", fill=c['slate_400'], font=f['sub'], anchor='lm')
    cy += 85

    # Category Pills
    cats = ["All Questions", "Registration", "User Login", "Events & RSVPs", "Technical Support"]
    cw = (fx2 - fx1 - 80) // len(cats)
    for i, clbl in enumerate(cats):
        cx = fx1 + i * (cw + 20)
        bg = c['indigo_600'] if i == 0 else c['white']
        tc = c['white'] if i == 0 else c['slate_800']
        draw_rounded_rectangle(draw, (cx, cy, cx + cw, cy + 45), radius=8, fill=bg, outline=c['slate_400'], width=1)
        draw.text((cx + cw//2, cy + 22), clbl, fill=tc, font=f['bold'], anchor='mm')
    cy += 70

    # Accordion FAQ items (4 items)
    faqs = [
        ("Q: How do I register for a new StudentHub student account?", "A: Go to the Registration page, fill in your authentic CHARUSAT email, student ID, and upload your ID proof document for verification."),
        ("Q: What should I do if I forget my login password?", "A: Click on 'Forgot Password' on the login page to receive an automated password reset link on your registered institutional email."),
        ("Q: How can I register and RSVP for upcoming campus events?", "A: Browse the Events page, select your desired technical or sports event, and click the 'Register RSVP' button."),
        ("Q: Who can access the Administrator Dashboard?", "A: Only authorized university administrators with administrative role-based access control privileges.")
    ]
    for i, (q, a) in enumerate(faqs):
        fy = cy + i * 140
        draw_rounded_rectangle(draw, (fx1, fy, fx2, fy + 120), radius=10, fill=c['white'], outline=c['slate_600'], width=2)
        draw.text((fx1 + 25, fy + 30), q, fill=c['slate_900'], font=f['header'])
        draw.text((fx1 + 25, fy + 75), a, fill=c['slate_600'], font=f['body'])

    return img

# --- PAGE 11: FEEDBACK ---
def generate_feedback():
    img, draw, W, H = create_base_canvas()
    f, c = get_fonts(), colors
    fx1, fy1, fx2, fy2 = draw_common_header(draw, W, "Feedback & Survey (feedback.html)", active_nav="Feedback")
    
    cy = fy1
    form_w = 1000
    form_x1 = (W - form_w) // 2
    form_x2 = form_x1 + form_w
    
    draw_rounded_rectangle(draw, (form_x1, cy, form_x2, fy2 - 20), radius=14, fill=c['white'], outline=c['slate_800'], width=3)
    fy = cy + 40
    
    draw.text((W//2, fy), "STUDENTHUB FEEDBACK & SURVEY", fill=c['slate_900'], font=f['header'], anchor='mm')
    draw.text((W//2, fy + 35), "We value your input! Help us improve the portal experience for everyone.", fill=c['slate_600'], font=f['sub'], anchor='mm')
    fy += 85

    # Star Rating
    draw.text((form_x1 + 50, fy), "Overall Satisfaction Rating *", fill=c['slate_900'], font=f['bold'])
    fy += 30
    draw.text((form_x1 + 50, fy), "★ ★ ★ ★ ☆   (4.0 out of 5.0 Stars)", fill=c['indigo_600'], font=f['header'])
    fy += 70

    # Name & Email
    draw.text((form_x1 + 50, fy), "Student Name (Optional)", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (form_x1 + 50, fy, form_x2 - 50, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((form_x1 + 70, fy + 22), "e.g. Aarav Patel", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 70

    # Category Dropdown
    draw.text((form_x1 + 50, fy), "Feedback Category *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (form_x1 + 50, fy, form_x2 - 50, fy + 45), radius=6, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((form_x1 + 70, fy + 22), "Portal UI & Usability", fill=c['slate_900'], font=f['body'], anchor='lm')
    draw.text((form_x2 - 80, fy + 22), "▼", fill=c['slate_600'], font=f['body'], anchor='mm')
    fy += 70

    # Detailed Comments
    draw.text((form_x1 + 50, fy), "Detailed Feedback & Suggestions *", fill=c['slate_900'], font=f['bold'])
    fy += 25
    draw_rounded_rectangle(draw, (form_x1 + 50, fy, form_x2 - 50, fy + 160), radius=8, fill=c['slate_100'], outline=c['slate_400'], width=1)
    draw.text((form_x1 + 70, fy + 30), "Write your detailed thoughts, bug reports, or feature suggestions here...", fill=c['slate_400'], font=f['body'], anchor='lm')
    fy += 190

    # Submit Button
    draw_rounded_rectangle(draw, (form_x1 + 50, fy, form_x2 - 50, fy + 55), radius=8, fill=c['indigo_600'])
    draw.text((W//2, fy + 27), "Submit Feedback", fill=c['white'], font=f['header'], anchor='mm')
    
    return img

def main():
    out_dir = "assets/images/wireframes"
    os.makedirs(out_dir, exist_ok=True)
    
    generators = [
        ("01_index_wireframe.png", generate_index),
        ("02_about_wireframe.png", generate_about),
        ("03_register_wireframe.png", generate_register),
        ("04_login_wireframe.png", generate_login),
        ("05_dashboard_wireframe.png", generate_dashboard),
        ("06_events_wireframe.png", generate_events),
        ("07_profile_wireframe.png", generate_profile),
        ("08_contact_wireframe.png", generate_contact),
        ("09_admin_wireframe.png", generate_admin),
        ("10_faq_wireframe.png", generate_faq),
        ("11_feedback_wireframe.png", generate_feedback)
    ]
    
    images_list = []
    
    for filename, gen_fn in generators:
        img = gen_fn()
        filepath = os.path.join(out_dir, filename)
        img.save(filepath, "PNG", dpi=(300, 300), optimize=True)
        images_list.append((filename, img))
        print(f"Generated {filepath}")
        
    # Also generate a master collage image showing all 11 wireframes side by side / grid!
    grid_cols = 3
    grid_rows = 4
    cw, ch = 1600, 2200
    gap = 80
    
    master_w = grid_cols * cw + (grid_cols + 1) * gap
    master_h = grid_rows * ch + (grid_rows + 1) * gap + 200 # top title padding
    
    master_img = Image.new('RGB', (master_w, master_h), color='#0F172A')
    mdraw = ImageDraw.Draw(master_img)
    
    mtitle_font = ImageFont.truetype("C:/Windows/Fonts/arialbd.ttf", 72)
    msub_font = ImageFont.truetype("C:/Windows/Fonts/arial.ttf", 36)
    
    mdraw.text((master_w//2, 80), "StudentHub Portal — Complete 11-Page Low-Fidelity UI Wireframe Showcase", fill='#FFFFFF', font=mtitle_font, anchor='mm')
    mdraw.text((master_w//2, 160), "High-Resolution Architectural UI Layout Wireframes for all HTML Pages", fill='#94A3B8', font=msub_font, anchor='mm')
    
    for idx, (filename, img) in enumerate(images_list):
        r = idx // grid_cols
        c = idx % grid_cols
        
        x = gap + c * (cw + gap)
        y = 240 + gap + r * (ch + gap)
        
        master_img.paste(img, (x, y))
        
    master_path = "assets/images/studenthub_all_11_wireframes.png"
    master_img.save(master_path, "PNG", dpi=(150, 150), optimize=True)
    print(f"Generated Master Collage at {master_path}")

if __name__ == '__main__':
    main()
