# LIVEWD-CRM-REALESTATE

/project-root
│
├── /config
│   └── Database.php                 # Database connection class
│
├── /classes
│   └── UserAuth.php                 # User authentication class (register, login, etc.)
│
├──                                 # Publicly accessible folder
│   ├── index.php                   # Landing page or dashboard (checks login)
│   ├── login.php                   # Login form + login logic
│   ├── logout.php                  # Logout handler
│   ├── register.php                # Registration form + logic
│   ├── forgot_password.php         # Request password reset form
│   ├── reset_password.php          # Password reset form (takes token)
│   └── assets                      # CSS, JS, images (if any)
│
├── /templates                      # Optional: reusable HTML templates (header, footer, etc.)
│   ├── header.php
│   └── footer.php
│
├── /vendor                        # Composer dependencies (if you use Composer)
│
└── README.md                      # Project overview and instructions
