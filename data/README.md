# How to Update Events

This folder contains the data for the website's calendar. The file you need to edit is named `events.json`.

## How to Add a New Event
1. Open the file `events.json`.
2. You will see a list of events. Each event looks like a block of text surrounded by curly braces `{ }`.
3. To add a new event, copy an existing block (from `{` to `}`), add a comma `,` after the last event, and paste your new block.
4. Change the text inside the quotes `""` to match your new event.

### Example of an Event Block:
```json
{
    "title": "Saturday Work Day",
    "start": "2025-05-10",
    "description": "Join us for sorting glasses! 8am - 12pm."
}
```

### Important Rules
- **Do NOT remove the quotes `""`**. Every piece of text must be inside quotes.
- **Dates must be YYYY-MM-DD**. For example: "2025-12-25".
- **Don't forget the comma**. If you have multiple events, there must be a comma `,` between the closing brace `}` of one event and the opening brace `{` of the next.
- **No comma after the last event**. The very last `}` in the list should NOT have a comma after it.

## Signups File
- **Do NOT edit `signups.json`**. This file is automatically updated when people sign up on the website.
