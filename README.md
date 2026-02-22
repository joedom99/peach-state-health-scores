# 🍑 Peach State Health Scores

**See inspection scores before you take a bite.**

Peach State Health Scores is a lightweight, map-first web app that makes Georgia restaurant health inspection scores easy to find and explore—starting around Georgia Tech / metro Atlanta and expanding as you browse. Built for the 2026 Georgia Tech Hacklytics hackathon.

- Live demo: https://health.countryfriedlabs.com  
- Devpost: https://devpost.com/software/peach-state-health-scores
- Video overview: https://youtu.be/9CnYg4TZfD0?si=NXNZvzKptP8Uqcwq

---

## What it does

- Interactive map with color-coded inspection grades (A/B/C/U)
- Filter by restaurant name and grade
- “Near Me” button to jump to your current location
- Loads instantly with preloaded Atlanta-area restaurants
- Optionally fetches live inspection records as you explore new areas
- Links back to the official Georgia DPH inspection pages for transparency

---

## Tech stack (keywords)

HTML, CSS, JavaScript, Leaflet.js, Mapbox Geocoding, PHP, REST API

---

## Data source

This project uses **public restaurant inspection data** from the Georgia Department of Public Health’s inspection lookup system located at https://ga.healthinspections.us/stateofgeorgia.

The official site is powered by Tyler Technologies and backed by a ColdFusion REST API. The API is undocumented, so we identified read-only endpoints by inspecting publicly served JavaScript and observing network requests used by the official interface.

This project:
- Uses **read-only** access to **public** inspection data
- Does **not** scrape behind logins
- Links back to the official DPH pages for full detail

---

## How it works (high level)

1. `index.html` renders the Leaflet map and loads a pre-geocoded starter dataset.
2. When users pan/zoom into supported areas, the app requests public inspection records for that city.
3. The DPH API returns addresses (no lat/lng), so the app geocodes addresses via Mapbox.
4. The app deduplicates restaurants, assigns grades, and displays markers on the map.
5. All markers include a link back to the official DPH report.

Because the DPH API does not set permissive CORS headers, a lightweight proxy is used.

---

## Running locally

### Option A: Frontend only (map + preloaded data)
Just open `index.html` in a browser.  
Note: live fetching may not work without the proxy.

### Option B: Run with PHP proxy (recommended)
1. Put `index.html` and `proxy.php` in the same folder.
2. From that folder, start a local PHP server: php -S localhost:8080
3.	Open: http://localhost:8080

### Mapbox token
This project uses Mapbox for geocoding.
Get your own free token at https://account.mapbox.com/access-tokens/ 
Then change this to your token
var MAPBOX_TOKEN = 'YOUR_PUBLIC_MAPBOX_TOKEN';
