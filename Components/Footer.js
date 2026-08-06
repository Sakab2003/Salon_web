import styles from '../styles/NavbarFooter.module.css';
import { userinfo } from '../Constants/userinfo'
import Link from 'next/link'
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faEnvelope } from "@fortawesome/free-solid-svg-icons";

const Footer = ({ currentTheme }) => {
    return (
        <div className={styles.footermain} style={{ backgroundColor: currentTheme.footerColor, color: currentTheme.subtext }}>
            <div className={styles.footertable}>
                <Link href='/'><a><h2 className={styles.footerlogo}>{userinfo.logoText}</h2></a></Link>
                <ul>
                    <li className={styles.listHeading}>Réseaux Sociaux</li>
                    {userinfo.socials ?
                        userinfo.socials.map((social, key) => {
                            return (
                                <Link href={social.link} key={key}><a target="_blank" rel="noopener noreferrer"><li style={{ display: 'flex', alignItems: 'center' }}><FontAwesomeIcon icon={social.icon} style={{ marginRight: '8px' }} /> {social.type.charAt(0).toUpperCase() + social.type.slice(1)}</li></a></Link>
                            )
                        }) : null
                    }
                    <Link href={`mailto:${userinfo.contact.email ? userinfo.contact.email : ''}`}><a><li style={{ display: 'flex', alignItems: 'center' }}><FontAwesomeIcon icon={faEnvelope} style={{ marginRight: '8px' }} /> Email</li></a></Link>
                </ul>
                <ul>
                    <li className={styles.listHeading}>Navigation</li>
                    <Link href='/'><a><li>Accueil</li></a></Link>
                    <Link href='/#about'><a><li>À Propos</li></a></Link>
                    <Link href='/work'><a><li>Projets</li></a></Link>
                    <Link href='/contact'><a><li>Contact</li></a></Link>
                </ul>
            </div>
            <hr style={{ height: '1px', backgroundColor: currentTheme.subtext, border: 'none', opacity: '0.5' }}></hr>
            <h2 className={styles.footercontent}>© 2026 Kaboré Samuel</h2>
        </div>
    )
}

export default Footer
