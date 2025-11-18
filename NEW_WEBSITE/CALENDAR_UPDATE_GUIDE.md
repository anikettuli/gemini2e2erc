# How to Update the Volunteer Calendar

## Overview
The volunteer calendar reads event data from `events.json`. To add, edit, or remove events, simply update this JSON file.

## Event Data Structure

Each event in `events.json` has the following fields:

```json
{
  "id": 1,                    // Unique number for the event
  "title": "Event Name",      // Name of the volunteer event
  "date": "2025-11-23",       // Date in YYYY-MM-DD format
  "time": "9:00 AM - 12:00 PM", // Time range
  "people": 15,               // Current number of registered volunteers
  "maxPeople": 20,            // Maximum capacity
  "location": "Main Center - 5621 Bunker Blvd, Watauga, TX 76148",
  "image": "images/events/colleyville-lions.jpg", // Path to event photo
  "description": "Brief description of the event",
  "contact": "volunteer@2e2erc.org" // Contact email
}
```

## How to Add a New Event

1. Open `events.json` in a text editor
2. Add a new event object to the array:

```json
{
  "id": 7,
  "title": "New Volunteer Event",
  "date": "2026-02-15",
  "time": "10:00 AM - 1:00 PM",
  "people": 0,
  "maxPeople": 20,
  "location": "Main Center - 5621 Bunker Blvd, Watauga, TX 76148",
  "image": "images/events/new-event.jpg",
  "description": "Description of what volunteers will do.",
  "contact": "volunteer@2e2erc.org"
}
```

3. Make sure to:
   - Use a unique `id` number
   - Format date as `YYYY-MM-DD`
   - Add a comma after the previous event
   - Keep proper JSON syntax (quotes, commas, brackets)

## How to Update Volunteer Count

When someone registers:
1. Find the event in `events.json`
2. Increase the `people` number
3. Save the file

Example:
```json
"people": 15,  // Change to 16 when someone registers
```

## How to Mark an Event as Full

When `people` equals `maxPeople`, the event automatically shows as FULL with a red badge.

## How to Remove Past Events

Simply delete the event object from the array, or keep it for historical records.

## Tips

- **Test your changes**: After editing, refresh the website to ensure proper JSON syntax
- **Date format matters**: Use `YYYY-MM-DD` format (e.g., `2025-12-25`)
- **Image paths**: Images are relative to the HTML file location
- **Missing images**: If an image doesn't exist, a placeholder will show automatically

## Example: Complete Update

Before:
```json
[
  {
    "id": 1,
    "title": "Event 1",
    "people": 10,
    ...
  }
]
```

After adding a new event:
```json
[
  {
    "id": 1,
    "title": "Event 1",
    "people": 10,
    ...
  },
  {
    "id": 2,
    "title": "New Event",
    "people": 0,
    ...
  }
]
```

## Common Mistakes to Avoid

❌ **Wrong date format**: `11/23/2025` (should be `2025-11-23`)  
❌ **Missing comma**: Between event objects  
❌ **Missing quotes**: Around text values  
❌ **Duplicate IDs**: Each event needs a unique ID  

✅ **Correct format**: See `events.json` for examples
