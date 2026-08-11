import { faGithubAlt, faLinkedinIn, faWhatsapp, faMediumM, faFacebook, faTwitter, faInstagram, faYoutube, faBehance } from "@fortawesome/free-brands-svg-icons"

export const userinfo = {
    logoText: "Kaboré Samuel", // Nom affiché sur la navbar et le footer
    contact: {
        email: 'kabores622@gmail.com', 
        phone: '05 53 11 99', 
        countrycode: '+226' 
    },
    socials: [
        { type: 'github', link: 'https://github.com/Sakab2003/', icon: faGithubAlt },
        { type: 'linkedin', link: 'https://www.linkedin.com/in/samuel-kabore-88a544346?utm_source=share_via&utm_content=profile&utm_medium=member_android', icon: faLinkedinIn },
        { type: 'whatsapp', link: 'https://wa.me/22605531199', icon: faWhatsapp },
    ],
    greeting: {
        title: "Bonjour, je suis Kaboré Samuel",
        subtitle: "Jeune diplômé en génie informatique, spécialisé en technologies high-tech, passionné par le développement web et les nouvelles technologies."
    },
    capabilities: [
        {
            category: "Développement Web",
            skills: ["PHP", "Laravel", "JavaScript"]
        },
        {
            category: "Frontend & UI",
            skills: ["HTML", "CSS", "Bootstrap"]
        },
        {
            category: "Bases de données & Outils",
            skills: ["MySQL", "WampServer", "Git"]
        },
        {
            category: "Bureautique & Autre",
            skills: ["Word", "PowerPoint", "Excel"]
        },
    ],
    about: {
        content: "Jeune diplômé en génie informatique, spécialisé en technologies high-tech, avec une solide formation académique et une soutenance validée avec une moyenne de 17/20[cite: 1]. Passionné par le développement et les nouvelles technologies, je suis à la recherche d'une première opportunité professionnelle afin de mettre en pratique mes compétences et contribuer à des projets innovants[cite: 1].",
        resume: "/CV_KABORE_SAMUEL.pdf" // Remplace par le lien de ton CV si hébergé en ligne
    },
    education: {
        visible: true, 
        educationList: [
            {
                time: '2026', 
                title: 'Licence Informatique', 
                organization: 'Université Aube Nouvelle', 
                description: 'Soutenance validée avec une moyenne de 17/20[cite: 1].'
            },
            {
                time: '2022',
                title: 'Baccalauréat (TleD)',
                organization: 'Établissement Gabriel Tabourin',
                description: 'Études secondaires et obtention du baccalauréat.'
            },
        ],
    },
    experience: {
        visible: true, 
        experienceList: [
            {
                company: 'DSI du MEBAPLN', 
                companylogo: '', 
                position: 'Stagiaire Développeur Web', 
                time: 'Mars 2025 - Janvier 2026', 
                description: 'Conception et développement d’une plateforme éducative interactive pour le suivi scolaire[cite: 1]. Analyse des besoins et modélisation UML[cite: 1]. Développement avec Laravel (PHP), Bootstrap, HTML, CSS, JavaScript et base de données MySQL sur WampServer[cite: 1].'
            },
        ],
    },
    blogs: {
        visible: false
    },
}

export const headings = {
    workHomePage: 'Projets Récents',
    workMainPage: 'Mes Projets',
    capabilities: 'Compétences',
    about: 'À Propos de Moi',
    education: 'Formation',
    experience: 'Expériences',
    blogs: 'Articles',
    contact: 'Contactez-moi',
}

export const ctaTexts = {
    landingCTA: 'Voir mes projets',
    workCTA: 'Voir plus',
    capabCTA: 'Me contacter',
    educationCTA: 'En savoir plus',
    resumeCTA: 'Mon CV',
    submitBTN: 'Envoyer'
}