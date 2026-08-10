/* eslint-disable import/no-anonymous-default-export */
export default async function (req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' })
  }

  console.log('Contact API request body:', req.body)

  const nodemailer = require('nodemailer')
  const transporter = nodemailer.createTransport({
    port: 465,
    host: 'smtp.gmail.com',
    auth: {
      user: process.env.SENDER,
      pass: process.env.PASSWORD,
    },
    secure: true,
  })

  const mailData = {
    from: `"${req.body.name}" <${process.env.SENDER}>`,
    to: process.env.TO,
    replyTo: req.body.email,
    subject: `Message From Portfolio Website - ${req.body.name}`,
    text: `${req.body.message} | Sent from: ${req.body.email}`,
    html: `<p>Name: ${req.body.name}</p><p>Email: ${req.body.email}</p><p>Phone: ${req.body.phone}</p><p>Message: ${req.body.message}</p>`,
  }

  try {
    const info = await transporter.sendMail(mailData)
    console.log('Email sent:', info.messageId)
    return res.status(200).json({ message: 'Email sent' })
  } catch (error) {
    console.error('Email send error:', error)
    return res.status(500).json({ error: 'Failed to send email' })
  }
}
