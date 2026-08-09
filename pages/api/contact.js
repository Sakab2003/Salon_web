/* eslint-disable import/no-anonymous-default-export */
export default function (req, res) {

  let nodemailer = require('nodemailer')
  const transporter = nodemailer.createTransport({
    port: 465,
    host: "smtp.gmail.com",
    auth: {
      user: process.env.SENDER,
      pass: process.env.PASSWORD,
    },
    secure: true,
  })
  const mailData = {
    from: process.env.SENDER,
    to: process.env.TO,
    subject: `Message From Portfolio Website`,
    text: req.body.message + " | Sent from: " + req.body.email,
    html: `<p>Name: ${req.body.name}</p><p>Email: ${req.body.email}</p><p>Phone: ${req.body.phone}</p><p>Message: ${req.body.message}</p>`
  }
  transporter.sendMail(mailData, function (err, info) {
    if (err) {
      console.error(err)
      return res.status(500).json({ error: 'Failed to send email' })
    }
    return res.status(200).json({ message: 'Email sent' })
  })
}
