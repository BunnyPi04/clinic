import { useState } from "react";
import { Link } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import apiClient from "../api/client";
import ErrorMessage from "../components/ErrorMessage";
import LoadingMessage from "../components/LoadingMessage";

async function fetchPatients(keyword) {
  const response = await apiClient.get("/patients", {
    params: keyword ? { q: keyword } : {},
  });

  return response.data;
}

export default function PatientsPage() {
  const [searchInput, setSearchInput] = useState("");
  const [keyword, setKeyword] = useState("");

  const patientsQuery = useQuery({
    queryKey: ["patients", keyword],
    queryFn: () => fetchPatients(keyword),
  });

  const patients = patientsQuery.data?.data || [];

  function handleSearch(event) {
    event.preventDefault();
    setKeyword(searchInput.trim());
  }

  return (
    <section>
      <div className="page-header">
        <div>
          <h1>Danh sách bệnh nhân</h1>
          <p>Tìm kiếm và quản lý hồ sơ bệnh nhân.</p>
        </div>

        <Link to="/patients/create" className="button button-primary">
          Thêm bệnh nhân
        </Link>
      </div>

      <form className="search-form" onSubmit={handleSearch}>
        <input
          value={searchInput}
          onChange={(event) => setSearchInput(event.target.value)}
          placeholder="Tìm tên, mã bệnh nhân, số điện thoại hoặc mã sổ..."
        />

        <button type="submit" className="button">
          Tìm kiếm
        </button>

        <button
          type="button"
          className="button button-secondary"
          onClick={() => {
            setSearchInput("");
            setKeyword("");
          }}
        >
          Xóa lọc
        </button>
      </form>

      {patientsQuery.isLoading && <LoadingMessage />}

      {patientsQuery.isError && (
        <ErrorMessage error={patientsQuery.error} />
      )}

      {!patientsQuery.isLoading && !patientsQuery.isError && (
        <div className="table-card">
          <table>
            <thead>
              <tr>
                <th>Mã bệnh nhân</th>
                <th>Họ tên</th>
                <th>Ngày sinh</th>
                <th>Điện thoại</th>
                <th>Bác sĩ chính</th>
                <th>Nguồn bệnh nhân</th>
                <th></th>
              </tr>
            </thead>

            <tbody>
              {patients.map((patient) => (
                <tr key={patient.id}>
                  <td>{patient.patient_code}</td>
                  <td>{patient.full_name}</td>
                  <td>{patient.date_of_birth || "—"}</td>
                  <td>{patient.phone || "—"}</td>
                  <td>{patient.primary_doctor?.full_name || "—"}</td>
                  <td>{patient.patient_source?.name || "—"}</td>
                  <td>
                    <Link to={`/patients/${patient.id}`}>Xem</Link>
                  </td>
                </tr>
              ))}

              {patients.length === 0 && (
                <tr>
                  <td colSpan="7" className="empty-cell">
                    Chưa có bệnh nhân phù hợp.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </section>
  );
}
