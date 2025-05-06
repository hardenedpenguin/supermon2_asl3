# Supermon2 for ASL3+

**Modification of Supermon2 to fully integrate with ASL3+**

## Overview

This project is a community-driven adaptation of Supermon2, tailored for full integration with AllStarLink version 3 (ASL3+). It provides a modern, web-based interface for monitoring and managing AllStar nodes, featuring:

- Real-time node status updates  
- Enhanced control panel functionalities  
- Improved compatibility with ASL3+ systems  

The repository includes PHP scripts, configuration files, and assets necessary for deployment on an ASL3+ node.

## ⚠️ Disclaimer

**This software is provided "as-is" with no official support.**  
It is intended for community use, experimentation, and improvement.  
There are no guarantees of stability, performance, or ongoing maintenance. Use at your own risk.

## Getting Started

### Prerequisites

- A running ASL3+ node  
- A web server with PHP support (e.g., Apache, Nginx)  
- Basic Linux system administration knowledge  

### Installation

1. Clone the repository to your web server’s root directory:

   ```bash
   git clone https://github.com/hardenedpenguin/supermon2_asl3.git supermon2
   ```

2. Set the appropriate permissions:

   ```bash
   chown -R www-data:www-data supermon2
   chmod -R 755 supermon2
   ```

3. Move the directory to our web server default servering location
   ```bash
   mv supermon2 /var/www/html/

5. Access the interface in your browser:  
   `http://your-server-ip/supermon2/`

### Optional: Enable Weather Display

If you wish to display current weather conditions within Supermon2, you will also need to install [`saytime_weather`](https://github.com/hardenedpenguin/saytime_weather):

1. Clone the `saytime_weather` repository:

   ```bash
   git clone https://github.com/hardenedpenguin/saytime_weather.git
   ```

2. Follow the setup instructions provided in that repository to configure weather updates for your node.

Weather integration is optional and not required for core Supermon2 functionality.

## Features

- **Node Monitoring**: View real-time status of connected nodes  
- **Control Panel**: Manage node connections and settings  
- **Optional Weather Integration**  
- **Customizable Interface**

## Contributing

Contributions are encouraged! If you have suggestions, bug fixes, or improvements:

- Fork the repo  
- Make your changes  
- Submit a pull request

For major changes, please open an issue first to discuss your ideas.

## License

This project is licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html).

## Acknowledgments

Thanks to the original authors of Supermon2 and the broader AllStarLink community for ongoing collaboration and inspiration.

---

*This project is not officially supported and is provided as-is. Community contributions are welcome.*  
