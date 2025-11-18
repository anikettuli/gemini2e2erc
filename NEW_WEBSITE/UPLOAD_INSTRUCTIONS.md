# How to Add Forms and Photos to the Website

## Adding Forms (PDFs)

1. Save your PDF forms in the `forms/` directory
2. The website already references these forms:
   - `forms/volunteer-registration.pdf`
   - `forms/mission-partner-application.pdf`
   - `forms/collection-box-request.pdf`
   - `forms/group-volunteer-form.pdf`

3. Simply upload your PDF files with these exact names to make them work

## Adding Photos/Images

1. Save your photos in the `images/` directory
2. Recommended photos to add:
   - `images/organization-photo.jpg` - Photo of the facility or team
   - `images/volunteers-working.jpg` - Volunteers sorting or working
   - `images/processing-glasses.jpg` - The eyeglass processing process
   - `images/sorting-process.jpg` - Sorting area
   - `images/facility.jpg` - Main facility

3. To replace placeholder images in the HTML:
   - Find the comment that says `<!-- Replace placeholder with actual photo: ... -->`
   - Uncomment the line with the actual image path
   - Comment out or remove the placeholder SVG image

## Example of replacing an image:

**Before:**
```html
<div class="image-placeholder">
    <!-- Replace placeholder with actual photo: <img src="images/volunteers-working.jpg" alt="Volunteers"> -->
    <img src="data:image/svg+xml,..." alt="Volunteers">
</div>
```

**After:**
```html
<div class="image-placeholder">
    <img src="images/volunteers-working.jpg" alt="Volunteers">
</div>
```

## Image Requirements

- Format: JPG, PNG, or WebP
- Recommended size: 800-1200px wide
- Optimize images before upload (use tools like TinyPNG or ImageOptim)
- Keep file sizes under 500KB for best performance
