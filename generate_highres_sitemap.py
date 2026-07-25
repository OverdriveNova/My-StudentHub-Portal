import os
from PIL import Image, ImageDraw, ImageFont

def draw_rounded_rectangle(draw, xy, radius, fill=None, outline=None, width=1):
    x1, y1, x2, y2 = xy
    draw.rounded_rectangle([x1, y1, x2, y2], radius=radius, fill=fill, outline=outline, width=width)

def main():
    W, H = 3600, 1100
    img = Image.new('RGB', (W, H), color='#F8FAFC')
    draw = ImageDraw.Draw(img)

    # Fonts
    font_path = "C:/Windows/Fonts/arial.ttf"
    font_bd_path = "C:/Windows/Fonts/arialbd.ttf"
    
    title_font = ImageFont.truetype(font_bd_path, 60)
    subtitle_font = ImageFont.truetype(font_path, 30)
    
    node_title_font = ImageFont.truetype(font_bd_path, 22)
    node_sub_font = ImageFont.truetype(font_path, 16)
    node_detail_font = ImageFont.truetype(font_path, 16)

    # Colors
    c_slate_900 = '#0F172A'
    c_slate_800 = '#1E293B'
    c_slate_600 = '#475569'
    c_slate_400 = '#94A3B8'
    c_slate_200 = '#E2E8F0'
    c_slate_100 = '#F1F5F9'
    c_indigo_600 = '#4F46E5'
    c_indigo_50 = '#EEF2FF'
    c_white = '#FFFFFF'

    # --- SITE MAP (ROOT TREE) ---
    draw.text((100, 50), "Site Map", fill=c_slate_900, font=title_font)
    draw.text((100, 125), "Root Tree & Visual Architecture Hierarchy (StudentHub Portal)", fill=c_slate_600, font=subtitle_font)

    # Root Node
    root_x, root_y = 1800, 200
    root_w, root_h = 440, 100
    rx1, ry1 = root_x - root_w//2, root_y
    rx2, ry2 = root_x + root_w//2, root_y + root_h
    
    draw_rounded_rectangle(draw, (rx1, ry1, rx2, ry2), radius=16, fill=c_indigo_50, outline=c_indigo_600, width=4)
    draw.text((root_x, ry1 + 25), "StudentHub (Root)", fill=c_slate_900, font=ImageFont.truetype(font_bd_path, 26), anchor='mm')
    draw.text((root_x, ry1 + 65), "Root Path: /", fill=c_indigo_600, font=ImageFont.truetype(font_bd_path, 18), anchor='mm')

    # Main Bus line
    bus_y = 370
    draw.line([(root_x, ry2), (root_x, bus_y)], fill=c_slate_800, width=4)

    # 11 Main Page Nodes
    pages = [
        ("Home", "index.html", []),
        ("About Us", "about.html", []),
        ("Student Registration", "register.html", ["Form Validation", "Personal Details", "Course & ID Upload"]),
        ("User Login", "login.html", ["User Authentication", "Password Check", "Role Check"]),
        ("Student Dashboard", "dashboard.html", ["Enrolled Courses", "Grades & GPA", "Class Schedule"]),
        ("Campus Events", "events.html", ["Upcoming Events", "Event Calendar", "RSVP & Tickets"]),
        ("Profile", "profile.html", ["Personal Info", "Academic Record", "Avatar Upload"]),
        ("Contact Support", "contact.html", ["Inquiry Form", "Location Map", "Helpline Info"]),
        ("Admin Portal", "admin.html", ["User Management", "Content Moderation", "System Audit Logs"]),
        ("FAQ", "faq.html", ["Searchable Questions", "Categories"]),
        ("Feedback", "feedback.html", ["Rating Form", "Comments"])
    ]

    margin_x = 180
    avail_w = 3240
    node_w = 270
    node_h = 95
    step_x = avail_w / (len(pages) - 1)
    
    draw.line([(margin_x, bus_y), (margin_x + avail_w, bus_y)], fill=c_slate_800, width=4)

    for i, (p_title, p_file, details) in enumerate(pages):
        nx = int(margin_x + i * step_x)
        ny1 = 430
        ny2 = ny1 + node_h
        nx1, nx2 = nx - node_w//2, nx + node_w//2

        # Vertical line from bus
        draw.line([(nx, bus_y), (nx, ny1)], fill=c_slate_800, width=3)

        # Node box
        draw_rounded_rectangle(draw, (nx1, ny1, nx2, ny2), radius=12, fill=c_white, outline=c_slate_800, width=3)
        draw.text((nx, ny1 + 30), p_title, fill=c_slate_900, font=node_title_font, anchor='mm')
        draw.text((nx, ny1 + 65), f"[{p_file}]", fill=c_indigo_600, font=node_sub_font, anchor='mm')

        # Child Details Box if present
        if details:
            cy1 = ny2 + 35
            ch = 35 + len(details) * 30
            cy2 = cy1 + ch
            
            draw.line([(nx, ny2), (nx, cy1)], fill=c_slate_600, width=2)
            draw_rounded_rectangle(draw, (nx1, cy1, nx2, cy2), radius=10, fill=c_slate_100, outline=c_slate_400, width=2)
            
            draw.text((nx, cy1 + 18), "Sub-components:", fill=c_slate_900, font=ImageFont.truetype(font_bd_path, 15), anchor='mm')
            for j, dtext in enumerate(details):
                draw.text((nx1 + 12, cy1 + 40 + j*28), f"• {dtext}", fill=c_slate_800, font=node_detail_font)

    # Save output
    output_path = "assets/images/studenthub_sitemap.png"
    img.save(output_path, "PNG", dpi=(300, 300), optimize=True)
    print(f"Successfully generated standalone high-resolution sitemap PNG at {os.path.abspath(output_path)}")

if __name__ == '__main__':
    main()
