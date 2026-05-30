# Makhanda SPCA Management System

## Overview

Developed a web-based management system to support SPCA animal welfare operations. The system centralises animal intake, medical care, kennel allocation, cruelty reporting, adoptions, volunteer management, and donation tracking to improve operational efficiency and data accuracy.

The platform includes role-based access for staff and veterinarians, with public-facing functionality that allows users to view adoptable animals and submit adoption applications without authentication.

## Key Features
- Animal intake and registration with kennel allocation and capacity rules  
- Medical record management including vaccinations, treatments, and sterilisation tracking  
- Cruelty reporting and investigation workflow with staff notifications  
- Adoption management with application screening and approval processes  
- Volunteer and community engagement tracking  
- Donation and fundraising management with high-value donation alerts  
- Reporting and analytics for animal welfare, resources, and participation metrics  

## Technical Highlights
- Implemented role-based access control (admin, veterinary staff, volunteers, public users)  
- Enforced business rules for animal welfare compliance and adoption eligibility  
- Designed a relational database structure to manage animals, users, reports, adoptions, and donations.
- Developed public-facing features with secure backend validation  

## Technology Stack
- HTML  
- CSS  
- JavaScript  
- PHP  
- MySQL  

## GitHub Pages (static demo)

The app can be published as a static site with in-browser mock data (no PHP/MySQL required on the host).

1. Run `npm run build:static` to generate the `dist/` folder.
2. Push to `main` — GitHub Actions deploys automatically (see `.github/workflows/deploy-pages.yml`).
3. Enable **Settings → Pages → Source: GitHub Actions**.

**Demo logins:** `admin` / `Admin123!` (Administrative Staff), `drsmith` / `Vet123!` (Veterinary Staff), `volunteer1` / `Vol123!` (Volunteer Staff). Details in [README-STATIC.md](README-STATIC.md).
