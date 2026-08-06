import styles from '../styles/Home.module.css'
import Link from 'next/link'
import { userinfo, ctaTexts, headings } from '../Constants/userinfo'
import { FaPhp, FaLaravel, FaHtml5, FaCss3Alt, FaReact, FaDatabase, FaGitAlt, FaServer, FaFileWord, FaFilePowerpoint, FaFileExcel, FaJsSquare } from 'react-icons/fa'
import { SiBootstrap, SiMysql } from 'react-icons/si'

const getIcon = (skillName) => {
    switch (skillName) {
        case 'PHP': return <FaPhp size={40} color="#777BB4" />;
        case 'Laravel': return <FaLaravel size={40} color="#FF2D20" />;
        case 'JavaScript': return <FaJsSquare size={40} color="#F7DF1E" />;
        case 'HTML': return <FaHtml5 size={40} color="#E34F26" />;
        case 'CSS': return <FaCss3Alt size={40} color="#1572B6" />;
        case 'Bootstrap': return <SiBootstrap size={40} color="#7952B3" />;
        case 'MySQL': return <SiMysql size={40} color="#4479A1" />;
        case 'WampServer': return <FaServer size={40} color="#FF00A0" />;
        case 'Git': return <FaGitAlt size={40} color="#F05032" />;
        case 'Word': return <FaFileWord size={40} color="#2B579A" />;
        case 'PowerPoint': return <FaFilePowerpoint size={40} color="#B7472A" />;
        case 'Excel': return <FaFileExcel size={40} color="#217346" />;
        default: return <FaDatabase size={40} />;
    }
}

const Skills = ({ currentTheme }) => {
    return (
        <>
            <h1 className={styles.workheading} data-aos="fade-up">{headings.capabilities}</h1>
            
            <div style={{ padding: '3rem 5vw', display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '2rem' }}>
                {userinfo.capabilities ?
                    userinfo.capabilities.map((value, key1) => {
                        return (
                            <div key={key1} data-aos="zoom-in" style={{
                                backgroundColor: currentTheme.name === 'light' ? '#ffffff' : '#1a1a2e',
                                padding: '2rem 1.5rem',
                                borderRadius: '20px',
                                boxShadow: currentTheme.name === 'light' ? '0 10px 30px rgba(0,0,0,0.05)' : '0 10px 30px rgba(0,0,0,0.3)',
                                border: `1px solid ${currentTheme.accent}33`,
                                transition: 'all 0.3s ease',
                                cursor: 'pointer'
                            }}
                            onMouseEnter={(e) => { e.currentTarget.style.transform = 'translateY(-10px)'; e.currentTarget.style.boxShadow = currentTheme.name === 'light' ? '0 20px 40px rgba(0,0,0,0.1)' : '0 20px 40px rgba(0,0,0,0.5)' }}
                            onMouseLeave={(e) => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = currentTheme.name === 'light' ? '0 10px 30px rgba(0,0,0,0.05)' : '0 10px 30px rgba(0,0,0,0.3)' }}
                            >
                                <h2 style={{ color: currentTheme.text, marginBottom: '2rem', textAlign: 'center', fontSize: '1.2rem', textTransform: 'uppercase', letterSpacing: '1px' }}>{value.category}</h2>
                                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '1.5rem', justifyItems: 'center' }}>
                                    {
                                        value.skills ?
                                            value.skills.map((skill, key2) => {
                                                return (
                                                    <div key={key2} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '0.8rem', transition: 'transform 0.2s', '&:hover': {transform: 'scale(1.1)'} }}
                                                    onMouseEnter={(e) => e.currentTarget.style.transform = 'scale(1.15)'}
                                                    onMouseLeave={(e) => e.currentTarget.style.transform = 'scale(1)'}
                                                    >
                                                        {getIcon(skill)}
                                                        <span style={{ color: currentTheme.subtext, fontSize: '0.85rem', fontWeight: 'bold', textAlign: 'center' }}>{skill}</span>
                                                    </div>
                                                )
                                            }) : null
                                    }
                                </div>
                            </div>
                        )
                    }) : null
                }
            </div>

            <div style={{ textAlign: 'center', padding: '1rem 0' }}>
                <Link href="/contact">
                    <a className={styles.cta3} style={{ background: 'transparent', border: `2px solid ${currentTheme.accent}` }}>
                        {ctaTexts.capabCTA} <span>&gt;</span>
                    </a>
                </Link>
            </div>
        </>
    )
}

export default Skills
