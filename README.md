<div id="top"></div>

<!-- PROJECT LOGO -->
<br />
<div align="center">
  <a href="https://canonizer.com">
    <img src="https://github.com/shahab-ramzan/read-me/blob/main/canonizer-fav.png" alt="Logo" width='80' >
  </a>
  <h3 align="center">Canonizer-3.0</h3>

  <p align="center">
    <br />
    <a href="https://canonizer.com/" style="color: #FFF;">View Demo</a>
    ·
    <a href="https://github.com/the-canonizer/canonizer-service/issues" style="color: #FFF;">Report Bug</a>
    ·
    <a href="https://github.com/the-canonizer/canonizer-service/issues" style="color: #FFF;">Request New Feature</a>
  </p>
</div>

<!-- TABLE OF CONTENTS -->
<details>
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#objective">Objective</a>
    </li>
    <li>
      <a href="#about-the-project">About The Project</a>
      <ul>
        <li><a href="#built-with">Built With</a></li>
      </ul>
    </li>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#prerequisites">Prerequisites</a></li>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
    <li>
      <a href="#contributing">Contributing</a>
      <ul>
        <li><a href="#Fork-this-repository">Fork this repository</a></li>
        <li><a href="#Create-a-branch">Create a branch</a></li>
        <li><a href="#Make-the-change">Make the change</a></li>
        <li><a href="#Push-the-change">Push the change</a></li>
      </ul>
    </li>
    <li><a href="#license">License</a></li>
    <li><a href="#contact">Contact</a></li>
  </ol>
</details>

## Objective
The objective of this repo is to provide the core building block for (https://canonizer.com/). This repo is responsible for managing initial data for canonizer by linkup with Canonizer3.0(https://github.com/the-canonizer/canonizer-3.0-api) and responsible to store data mongodb.

<!-- ABOUT THE PROJECT -->

## About The Project

[![Product Name Screen Shot][product-screenshot]](https://canonizer.com)

A wiki system that solves the critical liabilities of Wikipedia. It solves petty "edit wars" by providing contributors the ability to create and join camps and present their views without having them immediately erased. It also provides ways to standardize definitions and vocabulary, especially important in new fields.

<p align="right">(<a href="#top">back to top</a>)</p>

### Built With

- [Laravel Lumen](https://lumen.laravel.com/docs/8.x)
- [MongoDb](https://www.mongodb.com/)
- Unit Testing
  - [PHP Unit Testing](https://phpunit.de/)

<p align="right">(<a href="#top">back to top</a>)</p>

<!-- GETTING STARTED -->

## Getting Started

To get a local copy up and running follow these simple steps.

### Prerequisites

1. Git
2. PHP: version 7.4 or higher
3. MongoDb: version 6.0 or higher 
4. MySql
5. Composer(https://getcomposer.org/download/) 
5. A clone of the [canonizer-service](https://github.com/the-canonizer/canonizer-service) repo on your local machine

### Installation

1. Clone the repo
   ```sh
   git clone https://github.com/the-canonizer/canonizer-service.git
   ```
2. Go into the project root
   ```sh
   cd canonizer-service
   ```
3. Copy environment variables from `.env.example` to `.env` file
   ```sh
   cp .env.example .env
   ```
4. Install Dependency packages
   ```sh
   composer install
   ```
5. Make the mongodb connection string and place in .env
6. To start the hot-reloading development server
   ```sh
   php artisan serve
   ```
7. Run the command to make data on mongodb.
   ```sh
   php artisan tree:all
   ```
8. Check the status of results on postman
   ```sh
   open http://localhost:8002 
   ```

<p align="right">(<a href="#top">back to top</a>)</p>

<!-- CONTRIBUTING -->

## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please clone the repo and create a pull request. You can also simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

<img align="right" width="300" src="https://firstcontributions.github.io/assets/Readme/fork.png" alt="fork this repository" />

## Fork this repository

Fork this repository by clicking on the fork button on the top of this page.
This will create a copy of this repository in your account.

### Branch Naming Conventions

To contribute on it for any issue you can check the rules/example for naming the branches.
(https://docs.google.com/document/d/1qm5hqWfayHczDWOe74t-cLG7ovEJVa_jLhjICkaIjv8/)

### Create a branch

1. `git checkout master` from any folder in your local `canonizer-service` repository
2. `git pull origin master` to ensure you have the latest main code
3. `git checkout -b the-name-of-my-branch` (replacing `the-name-of-my-branch` with a suitable name) to create a branch

<!--
1. Clone the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Pretiffy the code for standard indendation (`npm run format`)
4. Make sure no one test case is being failed (`npm run test`)
5. Make sure Build is created successfuly (`npm run build`)
6. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
7. Push to the Branch (`git push origin feature/AmazingFeature`)
8. Open a Pull Request
 -->

### Make the change

1. Save the files and check by requesting on any internal API.

### Push the change

1. `git add -A && git commit -m "My message"` (replacing `My message` with a commit message, such as `Fix in logic of specific function`) to stage and commit your changes
2. `git push my-fork-name the-name-of-my-branch`
3. Go to the [canonizer-service repo](https://github.com/the-canonizer/canonizer-service) repo and you should see recently pushed branches.
4. Follow GitHub's instructions to create the Pull Request to master.
5. If possible, include screenshots of visual changes.

<p align="right">(<a href="#top">back to top</a>)</p>

<!-- LICENSE -->

## License

Will be added.

<!--
Distributed under the MIT License. See `LICENSE.txt` for more information.
 -->
<p align="right">(<a href="#top">back to top</a>)</p>

<!-- CONTACT -->

## Contact

Brent Allsop - [@Brent's_twitter](https://twitter.com/Brent_Allsop) - brent.allsop@gmail.com

Project Link: [https://canonizer.com](https://canonizer.com)

<p align="right">(<a href="#top">back to top</a>)</p>

<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->

[product-screenshot]: https://github.com/shahab-ramzan/read-me/blob/main/Canonizer%20(1).png
