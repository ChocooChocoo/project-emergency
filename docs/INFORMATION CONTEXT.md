# Emergency Rescue App — System Development Notes

## 1. System Overview

The proposed system is an emergency rescue application inspired by the on-demand booking model used by ride-hailing and delivery platforms such as Grab, FoodPanda, Angkas, and Move. Unlike these platforms, however, the system is purpose-built for emergency rescue services. This direction was developed in response to suggestions raised by the panel during the title defense. The scope of the system is limited to the City of Dasmariñas.

## 2. User-Side Features

Both registered and unregistered (guest) users are able to submit emergency rescue requests. Requests are categorized into two types:

- **One-Tap Emergency** — a simplified, immediate request for urgent situations requiring minimal input.
- **Detailed Emergency** — a request that allows the user to provide more specific information about the situation.

Following a revision, two additional request types were introduced:

- **Non-Emergency Request** — for situations that require assistance but are not classified as urgent emergencies.
- **Scheduled Rescue** — allows users to book a rescue/transport service in advance.

## 3. User Roles and Permissions

Because each partner organization differs in infrastructure and operational setup, the system implements **dynamic roles and permissions**. This means a single user account is not restricted to one fixed role; permissions can be combined or assigned flexibly depending on the needs and structure of the organization or facility involved.

## 4. User Registration

- Both **adults** and **minors** (with guardian consent/account linkage) are permitted to register.
- **Unregistered users** may still submit emergency requests but are limited to a reduced set of features. This limitation is intentional, serving as an incentive to encourage full user registration.

## 5. Organization Registration

Organizations seeking to register on the platform must own and operate at least one ambulance vehicle. To support this process, the development team needs to conduct interviews with various facilities that operate their own ambulances in order to determine:

- The criteria and process for verifying the **legitimacy** of an applying organization.
- The **official documents** required to be submitted as part of the registration process.
- The **approval workflow**, including which authorized personnel are responsible for reviewing and approving organization registrations.

## 6. Ambulance Transport Process

The current transport process protocol has been based on a review of **local and international standard procedures**. However, this process has **not yet been validated through direct interviews with ambulance drivers**, which is identified as a remaining task to ensure the protocol reflects real-world, field-level practice.

## 7. Remaining/Pending Items

- [ ] Conduct interviews with ambulance-owning facilities (for organization registration criteria/documents).
- [ ] Conduct interviews with ambulance drivers (to validate/refine the transport process).

---

_Notes compiled from verbal recollection during system development discussion; to be expanded with formal citations and interview data once available._