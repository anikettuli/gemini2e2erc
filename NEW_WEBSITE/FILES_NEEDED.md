# Files Upload Checklist for 2-E2 ERC Website

## ✅ COMPLETED - Files Already Organized

### Downloads Directory (`downloads/`)
- ✅ **ERC-Brochure.pdf** (1.5MB) - Organizational brochure
- ✅ **Helen-Keller-Award-Application.pdf** (208KB) - Lions Club award application
- ✅ **Mission-Request-Form.pdf** (289KB) - Form for missions to request eyeglasses

### Logo
- ✅ **e2e2rc_LOGO.png** - Already in place in NEW_WEBSITE directory

---

## ❌ STILL NEEDED - Files to Upload

### 1. Downloads Directory (`downloads/`)
**Missing File:**
- ❌ **Annual-Report.pdf** - Latest annual report showing impact, volunteer hours, and eyeglasses distributed worldwide

**Action:** Upload the most recent annual report PDF and name it exactly `Annual-Report.pdf`

---

### 2. Forms Directory (`forms/`)
**All 4 forms are missing:**
- ❌ **volunteer-registration.pdf** - Individual volunteer registration form
- ❌ **mission-partner-application.pdf** - Application for organizations to become mission partners
- ❌ **collection-box-request.pdf** - Form to request eyeglass collection boxes for locations
- ❌ **group-volunteer-form.pdf** - Form for groups (Lions Clubs, schools, churches) to schedule volunteer sessions

**Action:** Create or obtain these forms and upload them to the `forms/` directory

---

### 3. Event Photos (`images/events/`)
**All 7 event photos are missing:**
- ❌ **ncl-volunteers.jpg** - Photo of NCL (National Charity League) volunteers working
- ❌ **lions-pdg.jpg** - Photo of Past District Governors group
- ❌ **saturday-session.jpg** - Photo of 2nd Saturday volunteer session
- ❌ **colleyville-ncl.jpg** - Photo of Colleyville NCL volunteers
- ❌ **board-meeting.jpg** - Photo of ERC Board of Directors meeting
- ❌ **robson-ranch-lions.jpg** - Photo of Robson Ranch Lions Club volunteers
- ❌ **colleyville-lions.jpg** - Photo of Colleyville Lions Club volunteers

**Action:** Upload event photos showing volunteers in action. If specific photos don't exist, generic volunteer photos can be used with appropriate names.

**Recommended specs:**
- Format: JPG
- Resolution: 800x600px or higher (landscape orientation preferred)
- File size: Under 500KB each (optimize for web)

---

### 4. General Images (`images/`)
**All 3 general photos are missing:**
- ❌ **organization-photo.jpg** - Photo of the facility or team (used in About tab)
- ❌ **processing-glasses.jpg** - Photo showing the eyeglass processing/sorting work (used in Services tab)
- ❌ **volunteers-working.jpg** - Photo of volunteers working together (used in Get Involved tab)

**Action:** Upload photos showing the organization, facility, and volunteer activities.

**Recommended specs:**
- Format: JPG
- Resolution: 1200x800px or higher (landscape orientation)
- File size: Under 1MB each

---

## 📊 Summary Statistics

### Files Organized: 3/3 (100%)
- ERC Brochure ✅
- Helen Keller Award Application ✅
- Mission Request Form ✅

### Files Still Needed: 15 total
- **PDFs:** 5 files (1 download + 4 forms)
- **Images:** 10 photos (7 event photos + 3 general photos)

---

## 🎯 Priority Order

### HIGH PRIORITY (Website is functional but incomplete)
1. **Event Photos** (7 files) - Calendar events display placeholder until these are uploaded
2. **General Images** (3 files) - Key sections have placeholders

### MEDIUM PRIORITY (Nice to have)
3. **Annual Report PDF** (1 file) - Download section has 4 cards but only 3 work
4. **Forms PDFs** (4 files) - Forms section has download buttons but files don't exist yet

---

## 📁 Directory Structure

```
NEW_WEBSITE/
├── downloads/
│   ├── ERC-Brochure.pdf ✅
│   ├── Helen-Keller-Award-Application.pdf ✅
│   ├── Mission-Request-Form.pdf ✅
│   └── Annual-Report.pdf ❌ NEEDED
│
├── forms/
│   ├── volunteer-registration.pdf ❌ NEEDED
│   ├── mission-partner-application.pdf ❌ NEEDED
│   ├── collection-box-request.pdf ❌ NEEDED
│   └── group-volunteer-form.pdf ❌ NEEDED
│
├── images/
│   ├── organization-photo.jpg ❌ NEEDED
│   ├── processing-glasses.jpg ❌ NEEDED
│   ├── volunteers-working.jpg ❌ NEEDED
│   └── events/
│       ├── ncl-volunteers.jpg ❌ NEEDED
│       ├── lions-pdg.jpg ❌ NEEDED
│       ├── saturday-session.jpg ❌ NEEDED
│       ├── colleyville-ncl.jpg ❌ NEEDED
│       ├── board-meeting.jpg ❌ NEEDED
│       ├── robson-ranch-lions.jpg ❌ NEEDED
│       └── colleyville-lions.jpg ❌ NEEDED
│
└── e2e2rc_LOGO.png ✅
```

---

## 💡 Notes

### Image Placeholders
The website currently uses placeholder HTML comments where images should be. Once you upload the photos, the HTML structure is already in place and the images will display automatically.

### Form Creation
If the 4 forms in the `forms/` directory don't exist yet, you may need to:
1. Create them using the information from the old website
2. Use a template and customize it
3. Or create simple contact forms that direct people to email/call for now

### Temporary Solutions
- **Event photos:** Can use generic volunteer photos temporarily
- **Annual Report:** Can remove this download card if report doesn't exist yet
- **Forms:** Can convert these to "Contact Us" buttons that link to email if forms aren't ready

---

## 🚀 Quick Upload Commands

Once you have the files ready, use these commands to upload them:

```bash
# For PDFs
cp /path/to/your/Annual-Report.pdf /home/anike/claude2e2erc/NEW_WEBSITE/downloads/
cp /path/to/your/volunteer-registration.pdf /home/anike/claude2e2erc/NEW_WEBSITE/forms/
# ... etc

# For Images
cp /path/to/your/organization-photo.jpg /home/anike/claude2e2erc/NEW_WEBSITE/images/
cp /path/to/your/ncl-volunteers.jpg /home/anike/claude2e2erc/NEW_WEBSITE/images/events/
# ... etc
```

---

**Last Updated:** November 17, 2025
