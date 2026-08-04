# UI/UX Specification & Wireframe Structure: Manage Language Page

This document provides a detailed specification and layout structure extracted from the wireframe/mockup. It can be used as context or a prompt for AI code generators (e.g., v0, Cursor, ChatGPT, Claude) to build or style the frontend interface.

---

## 1. Page Overview
- **Page Name:** Manage Language (Admin Dashboard)
- **Target Audience/Role:** Admin user
- **Primary Functionality:** Manage programming/markup language definitions (Search, Add, Edit, Delete languages).

---

## 2. Layout Structure & Components

### 2.1 Top Navigation Bar (Header)
- **Left Alignment:**
  - Hamburger menu icon (`≡`) for toggling sidebar navigation.
- **Right Alignment:**
  - **User Info:** User profile avatar icon + Role label (`Admin`).
  - **Authentication:** `Log out` button (outlined pill shape).

---

### 2.2 Sidebar Navigation (Left Column)
- **Width:** Fixed width sidebar (~240px–280px).
- **Navigation Options:**
  1. **Manage Language** *(Active State)*
     - Styling: Dark background (black/dark fill), bold white text, rounded corners.
  2. **Manage Snippets** *(Inactive State)*
     - Styling: Muted grey background, light text, rounded corners.

---

### 2.3 Main Content Area (Manage Language View)

#### A. Header & Top Controls
- **Page Title:** `Manage Language`
- **Search Bar:**
  - Input field with rounded border (pill shape).
  - Search icon on the left inside input.
  - Placeholder / Input text: `python`
- **Action Button (Add):**
  - Circular dark button with a plus icon (`+`) for adding new language entries.

#### B. Data Table
- **Columns:**
  1. `ID` (Short width, neutral grey column background fill).
  2. `Language name` (Flexible width).
  3. `Action` (Action buttons column).

- **Sample Row Data:**
  - **ID:** `1`
  - **Language name:** `Python`
  - **Action:**
    - `Edit` Button (Secondary, neutral light grey background).
    - `Delete` Button (Destructive, red background with white text).

---

## 3. UI/UX Style & Design Tokens

| Token | Specification |
| :--- | :--- |
| **Color Palette** | Monochromatic base (White `#FFFFFF`, Dark Grey `#333333`, Black `#000000`) with Red accent (`#E53935`) for Delete actions |
| **Borders** | Clean black stroke borders for table and sidebar containers |
| **Corner Radius** | Pill-shapes for navigation buttons & search box; soft corners for table container |
| **Layout Model** | Sidebar layout: Left sidebar + Right dynamic main content area |

---

## 4. Prompt Template for AI Code Generation

If you are passing this to an AI model to generate frontend code (React / Tailwind / HTML-CSS), use the following prompt:

```text
Create a modern web application page based on the following specs:

- Framework: React (Next.js or Vite) with Tailwind CSS / Shadcn UI.
- Layout: Admin Dashboard with a fixed left sidebar and a top navigation bar.
- Top Bar: Includes hamburger menu icon on left, Admin profile avatar and Logout button on right.
- Sidebar: Contains two main menu links: "Manage Language" (active) and "Manage Snippets" (inactive).
- Main Section:
  - Header: "Manage Language" title with a search input field (pill-shaped) and an Add (+) button.
  - Table: Display language management records with columns [ID, Language name, Action].
  - Row Actions: "Edit" button (neutral) and "Delete" button (danger/red fill).
- Sample Data: ID = 1, Language name = "Python".