import axios from "axios";
import { useEffect, useState } from "react";

export default function App() {
  const [health, setHealth] = useState("Loading...");

  useEffect(() => {
    axios.get("http://localhost:8020/api/health")
      .then(res => setHealth(JSON.stringify(res.data)))
      .catch(() => setHealth("Laravel API not ready yet"));
  }, []);

  return (
    <div style={{ padding: "2rem", fontFamily: "Arial" }}>
      <h1>Clinic App Day 1</h1>
      <p>Frontend React is running.</p>
      <p>API status: {health}</p>
    </div>
  );
}
