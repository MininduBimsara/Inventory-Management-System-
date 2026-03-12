"use client";

import { AppShell } from "@/components/AppShell";
import { RequireAuth } from "@/components/RequireAuth";
import { apiRequest } from "@/lib/api";
import { formatDateTime, toErrorMessage } from "@/lib/format";
import { useEffect, useState } from "react";

type ActivityLog = {
  id: number;
  action: string;
  entity_type: string;
  entity_id: number;
  description: string | null;
  created_at: string;
  actor?: {
    id: number;
    name: string;
    email: string;
  };
  old_values: Record<string, unknown> | null;
  new_values: Record<string, unknown> | null;
};

type ActivityResponse = {
  data: ActivityLog[];
};

export default function ActivityLogPage(): React.ReactElement {
  const [logs, setLogs] = useState<ActivityLog[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const load = async (): Promise<void> => {
      try {
        const response = await apiRequest<ActivityResponse>("/activity-logs");
        setLogs(response.data);
      } catch (requestError) {
        setError(toErrorMessage(requestError));
      }
    };

    void load();
  }, []);

  return (
    <RequireAuth>
      <AppShell
        title="Activity Log"
        subtitle="Evaluation-critical audit trail including actor and before/after values"
      >
        {error ? <p className="error-text">{error}</p> : null}

        <div className="panel-card">
          <h3>Audit Entries</h3>
          <table className="table-grid">
            <thead>
              <tr>
                <th>When</th>
                <th>Actor</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Old</th>
                <th>New</th>
              </tr>
            </thead>
            <tbody>
              {logs.map((log) => (
                <tr key={log.id}>
                  <td>{formatDateTime(log.created_at)}</td>
                  <td>{log.actor?.name ?? "System"}</td>
                  <td>{log.action}</td>
                  <td>
                    {log.entity_type.split("\\").at(-1)} #{log.entity_id}
                  </td>
                  <td>
                    <pre>{JSON.stringify(log.old_values, null, 2)}</pre>
                  </td>
                  <td>
                    <pre>{JSON.stringify(log.new_values, null, 2)}</pre>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </AppShell>
    </RequireAuth>
  );
}
